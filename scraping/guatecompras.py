import sys
import requests
import re
import json
import datetime
from bs4 import BeautifulSoup


def json_serializer(obj):
    """JSON serializer for objects not serializable by default json code
       Specially dates and datetimes.
    """
    if isinstance(obj, (datetime.datetime, datetime.date)):
        return obj.isoformat()
    raise TypeError ("Type %s not serializable" % type(obj))

now                = datetime.datetime.now()
BASE_GTC_URI       = "http://www.guatecompras.gt"
DEFAULT_USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36'

OCDSConfig = {
    "uriPrefix": "http://www.ojoconmipisto.com/open-contracting/",
    "publisher": {
        "name": "Ojo con mi pisto",
        "scheme": None,
        "uid": "null",
        "uri": "http://www.ojoconmipisto.com"
    },
    "license": "http://opendatacommons.org/licenses/pddl/1.0/",
    "publishedDate": now,
    "publicationPolicy": "",
    "version": "1.1",
    "language": "es"
}

def getEmptyOCDSRecord(id):
    """ Returns an empty OCDS record dictionary
    """
    data = {
        "version": OCDSConfig["version"],
        "uri": OCDSConfig["uriPrefix"] + "records/" + id,
        "publisher": OCDSConfig["publisher"],
        "publishedDate": OCDSConfig["publishedDate"],
        "license": OCDSConfig["license"],
        "publicationPolicy": OCDSConfig["publicationPolicy"],
        "releases": [{
            "language": OCDSConfig["language"],
            "ocid": id,
            "id": id,
            "date": OCDSConfig["publishedDate"],
            "tag": ["tender"],
            "initiationType": "tender",
            "parties": [],
            "tender": {
                "documents": [],
                "items": [],
                "tenderers": []
            },
            "planning": {
                "documents": []
            },
            "bids": [],
            "awards": [],
            "contracts": [],
            "ocmp_extras": {
                "year": 0,
                "location": { "lat": 0, "lon": 0, "department": "Guatemala", "municipality": "Guatemala" },
                "identification": {
                    "NOG": "",
                    "SNIP": ""
                },
                "progress": {
                    "financial": 0,
                    "physical": 0
                },
                "alcalde": "",
                "partido": ""
            }
        }],
        "extensions":[
            "https://raw.githubusercontent.com/Guitlle/wp-ocds/ocmp_extension/schema.json",
            "https://raw.githubusercontent.com/open-contracting/ocds_bid_extension/v1.1.1/extension.json"]
        ],
    }
    return data

""" Mappings from non-stardad values to standard OCDS values.
"""

mainProcurementCategoriesMap = {
    "Construcción y materiales afines": "works"
}

tenderStatusMap = {
    "Terminado adjudicado": "complete",
    "Vigente (se aceptan ofertas)": "active"
}

ESMonthToNumberMap = {
    "enero": 1,
    "febrero": 2,
    "marzo": 3,
    "abril": 4,
    "mayo": 5,
    "junio": 6,
    "julio": 7,
    "agosto": 8,
    "septiembre": 9,
    "octubre": 10,
    "noviembre": 11,
    "diciembre": 12
}

# Parse dates in the format '10.noviembre.2017 Hora:08:24:02 p.m.'
def ParseUglyDate(fecha):
    print("parsing ugly date", fecha)
    matches = re.match("(\d*)\.(\w*)\.(\d*) Hora\:(\d*)\:(\d*):(\d*) ((p|a)\.m)", fecha)
    if matches is not None:
        try:
            data = matches.groups()
            hora = int(data[3])
            if data[6]=="p.m":
                if hora < 12:
                    hora += 12
                elif hora == 12:
                    hora = 24
            else:
                if hora == 12:
                    hora = 0

            return datetime.datetime(
                int(data[2]),
                ESMonthToNumberMap[data[1]],
                int(data[0]),
                hora,
                int(data[4]),
                int(data[5])
            )
        except:
            return None
    return None

def ExtractHTMLFromUglyASPResponse(input):
    lines = tab.text.splitlines()
    htmlContent = ""
    flag = 0
    for line in lines:
        line = line.strip()
        if len(line) == 0:
            continue
        if flag == 1:
            if line[0] == "|" and flag == 1:
                break
            else:
                htmlContent += "\n" + line
        elif "|MasterGC_ContentBlockHolder_ctl01|" in line:
            print("found start ", line)
            flag = 1

    return htmlContent

def FetchMainGTCRecord(NOG):
    # Obtener página principal
    url = "http://guatecompras.gt/concursos/consultaConcurso.aspx?nog={}&o=5".format(NOG)
    print("Fetching main webpage for tender with NOG ", NOG, "at ", url)
    main = requests.get(url)
    main_soup = BeautifulSoup(main.text, 'html.parser')

    if main_soup.find(id="MasterGC_ContentBlockHolder_divDetalleConcurso") is None: raise Exception("No tender data found.")

    # If all good :
    details_cells = main_soup.find(id="MasterGC_ContentBlockHolder_divDetalleConcurso").find_all("td")
    details_data = {}

    for i in range(len(details_cells)):
        if i%2 == 0:
            attr = re.sub("[^\w\ ]*", "", details_cells[i].text.lower()).strip()
        else:
            details_data[attr] = details_cells[i].text.strip()
            details_data["html " + attr] = details_cells[i]

    # Obtener las vistas parciales de las pestañas inferiores

    # Primero va la vista parcial de documentos anexos:
    formData = {
        "__VIEWSTATE" : main_soup.find(id = "__VIEWSTATE").get("value"),
        "__VIEWSTATEGENERATOR" : main_soup.find( id = "__VIEWSTATEGENERATOR").get("value"),
        "MasterGC$ContentBlockHolder$scriptManager" : "MasterGC$ContentBlockHolder$ctl01|MasterGC$ContentBlockHolder$RadTabStrip1",
        "MasterGC$svrID" : "4",
        "MasterGC_ContentBlockHolder_RadTabStrip1_ClientState" : '{"selectedIndexes":["1"],"logEntries":[],"scrollState":{}}',
        "MasterGC_ContentBlockHolder_RMP_Historia_ClientState" : "",
        "MasterGC_ContentBlockHolder_wcuConsultaConcursoDetalleEjecucion_RadToolTipManager2_ClientState": "",
        "__EVENTTARGET": "MasterGC$ContentBlockHolder$RadTabStrip1",
        "__EVENTARGUMENT": '{"type":0,"index":"0"}',
        "__ASYNCPOST": "true"
    };
    tab = requests.post("http://guatecompras.gt/concursos/consultaConcurso.aspx?nog={}&o=5".format(NOG), data = formData, headers = {
        'User-Agent': DEFAULT_USER_AGENT
    })
    htmlcontent = ExtractHTMLFromUglyASPResponse(tab.text)

    tiposAnexo = BeautifulSoup(htmlcontent, "html.parser")
    docs = []
    SNIP = None
    for table in tiposAnexo.find_all("table", "TablaDetalle"):
        header = table.find_all("th")
        if header[0].text == "Tipo de documento(s)":
            for row in table.find_all("tr","FilaTablaDetalle"):
                data = row.find_all("td")
                docs.append({
                    "type": data[0].text.strip(),
                    "link": data[1].find("a").get("href").strip(),
                    "responsible": data[2].text.strip()
                })
        elif header[1].text == "SNIP":
            data = table.find("td").text
            found = re.findall("SNIP\: (\d*)", data)
            if len(found) == 1:
                SNIP = found[0]

    details_data["snip"] = SNIP

    # Fetch items information
    formData["MasterGC_ContentBlockHolder_RadTabStrip1_ClientState"] = '{"selectedIndexes":["2"],"logEntries":[],"scrollState":{}}',
    tab = requests.post("http://guatecompras.gt/concursos/consultaConcurso.aspx?nog={}&o=5".format(NOG), data = formData, headers = {
        'User-Agent': DEFAULT_USER_AGENT
    })
    htmlcontent = ExtractHTMLFromUglyASPResponse(tab.text)
    itemsData = BeautifulSoup(htmlcontent, "html.parser")
    items = []
    for row in table.find_all("tr","FilaTablaDetalle"):
        data = row.find_all("td")
        item = {
            "id": len(items),
            "description": data[0],
            "quantity": data[1],
            "unit": data[2]
        }

    # Awards and tenderers
    formData["MasterGC_ContentBlockHolder_RadTabStrip1_ClientState"] = '{"selectedIndexes":["3"],"logEntries":[],"scrollState":{}}',
    tab = requests.post("http://guatecompras.gt/concursos/consultaConcurso.aspx?nog={}&o=5".format(NOG), data = formData, headers = {
        'User-Agent': DEFAULT_USER_AGENT
    })
    htmlcontent = ExtractHTMLFromUglyASPResponse(tab.text)
    tabData = BeautifulSoup(htmlcontent, "html.parser")
    # TODO : build award and tenderers data

    # Contracting
    formData["MasterGC_ContentBlockHolder_RadTabStrip1_ClientState"] = '{"selectedIndexes":["4"],"logEntries":[],"scrollState":{}}',
    tab = requests.post("http://guatecompras.gt/concursos/consultaConcurso.aspx?nog={}&o=5".format(NOG), data = formData, headers = {
        'User-Agent': DEFAULT_USER_AGENT
    })
    htmlcontent = ExtractHTMLFromUglyASPResponse(tab.text)
    tabData = BeautifulSoup(htmlcontent, "html.parser")
    # TODO : build award and tenderers data

    # Historial
    formData["MasterGC_ContentBlockHolder_RadTabStrip1_ClientState"] = '{"selectedIndexes":["5"],"logEntries":[],"scrollState":{}}',
    tab = requests.post("http://guatecompras.gt/concursos/consultaConcurso.aspx?nog={}&o=5".format(NOG), data = formData, headers = {
        'User-Agent': DEFAULT_USER_AGENT
    })
    htmlcontent = ExtractHTMLFromUglyASPResponse(tab.text)
    tabData = BeautifulSoup(htmlcontent, "html.parser")
    # TODO : build award and tenderers data


    return {
        "base": details_data,
        "documents": docs,
        "items": items,
        "tenderers": tenderers,
        "awards": awards

def UpdateData(baseData, details_data, documents, items):
    # Assume there is a NOG id
    baseData["releases"][0]["ocmp_extras"]["identification"]["NOG"] = details_data["nog"]
    baseData["releases"][0]["ocmp_extras"]["identification"]["SNIP"] = details_data["snip"]

    # Description
    if "descripción" in details_data:
        baseData["releases"][0]["description"] = baseData["releases"][0]["tender"]["title"] = details_data["descripción"]

    # Dates
    baseData["releases"][0]["tender"]["tenderPeriod"] = {}
    baseData["releases"][0]["tender"]["awardPeriod"] = {}
    if "fecha de cierre de recepción de ofertas" in details_data:
        baseData["releases"][0]["tender"]["tenderPeriod"]["endDate"] = ParseUglyDate(details_data["fecha de cierre de recepción de ofertas"])
    if "fecha de finalización" in details_data:
        baseData["releases"][0]["tender"]["awardPeriod"]["endDate"]  = ParseUglyDate(details_data["fecha de finalización"])
    if "fecha de presentación de ofertas" in details_data:
        baseData["releases"][0]["tender"]["tenderPeriod"]["startDate"] = ParseUglyDate(details_data["fecha de presentación de ofertas"])
    if "fecha de publicación" in details_data:
        baseData["releases"][0]["publishedDate"] = baseData["releases"][0]["date"] = ParseUglyDate(details_data["fecha de publicación"])

    # Default main category is "works"
    baseData["releases"][0]["tender"]["mainProcurementCategory"] = mainProcurementCategoriesMap.get(details_data["categoría"], "works")
    baseData["releases"][0]["tender"]["additionalProcurementCategories"] = [ details_data["categoría"] ]

    # Entidad compradora
    entity = {}
    entity["id"] = len(baseData["releases"][0]["parties"])
    entity["name"] = details_data["entidad"]
    entity["details"] = {
        "guatecompras_uri" : BASE_GTC_URI + details_data["html entidad"].find("a").get("href"),
        "type": details_data["tipo de entidad"] if "tipo de entidad" in details_data else "unknown",
        "unit": details_data["unidad compradora"] if "unidad compradora" in details_data else "unknown",
    }
    baseData["releases"][0]["tender"]["procuringEntity"] = \
        baseData["releases"][0]["buyer"] = \
            { "name": entity["name"], "id": entity["id"] }
    baseData["releases"][0]["parties"].append(entity)

    # status
    baseData["releases"][0]["tender"]["status"] = tenderStatusMap.get(details_data["estatus"], "")

    # procurement method
    if details_data.get("tipo de proceso") == "Adquisición Competitiva":
        if details_data.get("tipo de concurso") == "Público":
            baseData["releases"][0]["tender"]["procurementMethod"] = "open"
    baseData["releases"][0]["tender"]["procurementMethodDetails"] = "Modalidad: {}, Tipo de Proceso: {}, Tipo de concurso: {}".format(
        details_data.get("modalidad", ""),
        details_data.get("tipo proceso", ""),
        details_data.get("tipo de concurso", "")
    )

    submethod = details_data.get("recepción de ofertas", "")
    if submethod.startswith("Sólo en papel"):
        baseData["releases"][0]["tender"]["submissionMethod"] = "written"


    # Documents mapping
    i = 0
    for doc in docs:
        doc = {
            "description": "",
            "format": "pdf",
            "language": "es"
            "id": i,
            "title": doc["type"],
            "url":  doc["link"]
        }
        if  doc["type"] == "Anuncio, convocatoria o invitación":
            doc["documentType"] = "tenderNotice"
            baseData["releases"][0]["tender"]["documents"].append(doc)
        elif doc["type"] == "Bases, especificaciones generales o términos de referencia":
            doc["documentType"] = "technicalSpecifications"
            baseData["releases"][0]["tender"]["documents"].append(doc)
        elif doc["type"] == "Boleta de SNIP":
            doc["documentType"] = "projectPlan"
            baseData["releases"][0]["planning"]["documents"].append(doc)
        elif doc["type"] == "Criterio de calificación":
            doc["documentType"] = "evaluationCriteria"
            baseData["releases"][0]["tender"]["documents"].append(doc)
        elif doc["type"] == "Dictamen de aprobación de estudio de factibilidad":
            doc["documentType"] = "x_feasibilityStudyAssessment"
            baseData["releases"][0]["planning"]["documents"].append(doc)
        elif doc["type"] == "Dictamen de aprobación de impacto ambiental":
            doc["documentType"] = "x_environmentalImpactAssessment"
            baseData["releases"][0]["planning"]["documents"].append(doc)
        elif doc["type"] == "Dictamen técnico":
            doc["documentType"] = "x_technicalAssessment"
            baseData["releases"][0]["planning"]["documents"].append(doc)
        elif doc["type"] == "Diseño del Proyecto":
            doc["documentType"] = "x_projectDesign"
            baseData["releases"][0]["planning"]["documents"].append(doc)
        elif doc["type"] == "Estudio de Factibilidad":
            doc["documentType"] = "feasibilityStudy"
            baseData["releases"][0]["planning"]["documents"].append(doc)
        elif doc["type"] == "Estudio de impacto ambiental":
            doc["documentType"] = "environmentalImpact"
            baseData["releases"][0]["planning"]["documents"].append(doc)
        elif doc["type"] == "Estudios, diseños o planos":
            doc["documentType"] = "x_otherStudies"
            baseData["releases"][0]["planning"]["documents"].append(doc)
        elif doc["type"] == "Modelo de oferta (formulario)":
            doc["documentType"] = "contractDraft"
            baseData["releases"][0]["tender"]["documents"].append(doc)
        elif doc["type"] == "Opinión jurídica":
            doc["documentType"] = "x_legalOpinion"
            baseData["releases"][0]["planning"]["documents"].append(doc)
        elif doc["type"] == "Proyecto de bases":
            doc["documentType"] = "procurementPlan"
            baseData["releases"][0]["planning"]["documents"].append(doc)
        elif doc["type"] == "Resolución de aprobación de bases":
            doc["documentType"] = "x_procurementApproval"
            baseData["releases"][0]["tender"]["documents"].append(doc)
        elif doc["type"] == "Selección de supervisor de la obra":
            doc["documentType"] = "x_supervisorSelection"
            baseData["releases"][0]["tender"]["documents"].append(doc)
        elif doc["type"] == "Solicitud o requerimiento de bien, servicio o suministro":
            doc["documentType"] = "needsAssessment"
            baseData["releases"][0]["planning"]["documents"].append(doc)

        i += 1

    # Products types or items
    baseData["releases"][0]["tender"]["items"].extend(items)

    return baseData


if __name__ == "__main__":
    print("Guatecompras scraper. Outputs OCDS record.\nUsage: guatecompras.py OCID NOG")
    ocid = sys.argv[1]
    nog = sys.argv[2]
    newRecord = getEmptyOCDSRecord(ocid)
    mainData, documents, items = FetchMainGTCRecord(nog)
    newRecord = UpdateData(newRecord, mainData, documents, items)
    print( json.dumps(newRecord, indent = 4, sort_keys = True, default=json_serializer ) )
