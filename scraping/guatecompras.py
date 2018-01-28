import sys
import requests
import re
import json
import datetime
from bs4 import BeautifulSoup

# Custom serializer that handles dates and datetimes
def JSONSerializer(obj):
    """JSON serializer for objects not serializable by default json code
       Specially dates and datetimes.
    """
    if isinstance(obj, (datetime.datetime, datetime.date)):
        return obj.isoformat()
    raise TypeError ("Type %s not serializable" % type(obj))

# Current time
now                = datetime.datetime.now()

# Constants
GTC_CURRENCY       = "GTQ"
BASE_GTC_URI       = "http://www.guatecompras.gt"
DEFAULT_USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36'

# OCDS Helpers
# Values for OCDS records and releases headers.
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

def GetEmptyOCDSRecord(id):
    """ Returns an empty OCDS record dictionary with default values
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
            "bids": {
                "statistics": [],
                "details": []
            },
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
            "https://raw.githubusercontent.com/open-contracting/ocds_bid_extension/v1.1.1/extension.json"
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

# Mapping from month in lowercase spanish to number
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

# Helper functions

def ParseUglyDate(fecha):
    """ Parse dates in the format '10.noviembre.2017 Hora:08:24:02 p.m.'
    These is the format used in guatecompras. Hopefully it won't change.
    """
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

def ExtractHTMLFromUglyASPResponse(responseText):
    """ Extract the HTML from an ASP.NET response obtained from guatecompras.
    These ajax responses contain lots of fields that are apparently separated
    by | chars.
    """
    lines = responseText.splitlines()
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
            flag = 1

    return htmlContent

def FetchData(NOG):
    """ Fetch data from guatecompras website using as input the NOG id.
    This function returns a dictionary with raw data. This raw data shall
    be used later to build the ocds record
    """

    response = {}

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
    tabs = main_soup.find("div", id = "MasterGC_ContentBlockHolder_divContenidoTab").find_all("li", "rtsLI")
    tabsNames = [tab.text for tab in tabs]
    print("Tabs available for this record: ", tabsNames)
    # Primero va la vista parcial de documentos anexos:
    if "Tipos de anexo" in tabsNames:
        print("Fetching \"Tipos de anexo\"")
        tabIndex = tabsNames.index("Tipos de anexo")
        formData = {
            "__VIEWSTATE" : main_soup.find(id = "__VIEWSTATE").get("value"),
            "__VIEWSTATEGENERATOR" : main_soup.find( id = "__VIEWSTATEGENERATOR").get("value"),
            "MasterGC$ContentBlockHolder$scriptManager" : "MasterGC$ContentBlockHolder$ctl01|MasterGC$ContentBlockHolder$RadTabStrip1",
            "MasterGC$svrID" : "4",
            "MasterGC_ContentBlockHolder_RadTabStrip1_ClientState" : '{"selectedIndexes":["' + str(tabIndex) + '"],"logEntries":[],"scrollState":{}}',
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
                data  = table.find("td").text
                found = re.findall("SNIP\: (\d*)", data)
                if len(found) == 1:
                    SNIP = found[0]

        details_data["snip"] = SNIP
        response["documents"] = docs

    response["base"]      = details_data

    # Fetch items information
    if "Tipos de Producto" in tabsNames:
        print("Fetching \"Tipos de Producto\"")
        tabIndex = tabsNames.index("Tipos de Producto")

        formData["MasterGC_ContentBlockHolder_RadTabStrip1_ClientState"] = '{"selectedIndexes":["' + str(tabIndex) + '"],"logEntries":[],"scrollState":{}}',
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
        response["items"] = items

    # Awards and tenderers
    if "Proceso de Adjudicación" in tabsNames:
        print("Fetching \"Proceso de Adjudicación\"")
        tabIndex = tabsNames.index("Proceso de Adjudicación")

        bids = []
        awards = []

        formData["MasterGC_ContentBlockHolder_RadTabStrip1_ClientState"] = '{"selectedIndexes":["' + str(tabIndex) + '"],"logEntries":[],"scrollState":{}}',
        tab = requests.post("http://guatecompras.gt/concursos/consultaConcurso.aspx?nog={}&o=5".format(NOG), data = formData, headers = {
            'User-Agent': DEFAULT_USER_AGENT
        })
        htmlcontent = ExtractHTMLFromUglyASPResponse(tab.text)
        tabData = BeautifulSoup(htmlcontent, "html.parser")
        bidsTbl = tabData.find("table", id="MasterGC_ContentBlockHolder_wcuConsultaConcursoAdjudicaciones_gvOfertas")
        awardsTbl = tabData.find("table", id="MasterGC_ContentBlockHolder_wcuConsultaConcursoAdjudicaciones_gvAdjudicacion")

        for bidRow in bidsTbl.find_all("tr", "FilaTablaDetalle"):
            data = bidRow.find_all("td")
            bids.append({
                "supplierLink": data[0].find("a").get("href"),
                "supplierId": data[0].text.strip(),
                "supplierName": data[1].text.strip(),
                "insuranceName": data[2].text.strip(),
                "value": data[3].text.strip(),
                "insuranceValue": data[4].text.strip()
            })

        for awRow in awardsTbl.find_all("tr", "FilaTablaDetalle"):
            data = awRow.find_all("td")
            awards.append({
                "supplierLink": data[0].find("a").get("href"),
                "supplierId": data[0].text.strip(),
                "supplierName": data[1].text.strip(),
                "contract": data[2].text.strip(),
                "value": data[3].text.strip()
            })
        response["awards"] = awards
        response["bids"] = bids

    # Contracting
    if "Suscripción de contrato" in tabsNames:
        print("Fetching \"Suscripción de contrato\"")
        tabIndex = tabsNames.index("Suscripción de contrato")

        contractDocs = []

        formData["MasterGC_ContentBlockHolder_RadTabStrip1_ClientState"] = '{"selectedIndexes":["' + str(tabIndex) + '"],"logEntries":[],"scrollState":{}}',
        tab = requests.post("http://guatecompras.gt/concursos/consultaConcurso.aspx?nog={}&o=5".format(NOG), data = formData, headers = {
            'User-Agent': DEFAULT_USER_AGENT
        })
        htmlcontent = ExtractHTMLFromUglyASPResponse(tab.text)
        tabData = BeautifulSoup(htmlcontent, "html.parser")
        fase1 = tabData.find("div", id= "MasterGC_ContentBlockHolder_wcuConsultaConcursoSucripcionContrato_acFaseContratacion")
        if fase1 is not None:
            titles = [t.text.strip() for t in fase1.find_all("b")]
            links = [t.get("href") for t in fase1.find_all("a")]
            for name, link in zip(titles, links):
                contractDocs.append({
                    "name": name, "link": link, "fase": 1
                })

        fase2 = tabData.find("div", id= "MasterGC_ContentBlockHolder_wcuConsultaConcursoSucripcionContrato_divSuscipcionContrato")
        if fase2 is not None:
            for doc in fase2.find_all("tr", "FilaTablaDetalle"):
                data = doc.find_all("td")
                contractDocs.append({
                    "name": "unknown", "link": data[6].find("a").get("href"), "fase": 2,
                    "supplierName": data[0].text.strip(),
                    "contractNumber": data[1].text.strip(),
                    "insuranceName": data[2].text.strip(),
                    "contractType": data[3].text.strip(),
                    "contractValue": data[4].text.strip(),
                    "insuranceValue": data[5].text.strip()
                })

        response["contractDocs"] = contractDocs

    return response

def UpdateData(baseData, rawData):
    # Assume there is a NOG id
    baseData["releases"][0]["ocmp_extras"]["identification"]["NOG"] = rawData["base"]["nog"]
    baseData["releases"][0]["ocmp_extras"]["identification"]["SNIP"] = rawData["base"]["snip"]

    # Description
    if "descripción" in rawData["base"]:
        baseData["releases"][0]["description"] = baseData["releases"][0]["tender"]["title"] = rawData["base"]["descripción"]

    # Dates
    baseData["releases"][0]["tender"]["tenderPeriod"] = {}
    baseData["releases"][0]["tender"]["awardPeriod"] = {}
    if "fecha de cierre de recepción de ofertas" in rawData["base"]:
        baseData["releases"][0]["tender"]["tenderPeriod"]["endDate"] = ParseUglyDate(rawData["base"]["fecha de cierre de recepción de ofertas"])
    if "fecha de finalización" in rawData["base"]:
        baseData["releases"][0]["tender"]["awardPeriod"]["endDate"]  = ParseUglyDate(rawData["base"]["fecha de finalización"])
    if "fecha de presentación de ofertas" in rawData["base"]:
        baseData["releases"][0]["tender"]["tenderPeriod"]["startDate"] = ParseUglyDate(rawData["base"]["fecha de presentación de ofertas"])
    if "fecha de publicación" in rawData["base"]:
        baseData["releases"][0]["publishedDate"] = baseData["releases"][0]["date"] = ParseUglyDate(rawData["base"]["fecha de publicación"])

    # Default main category is "works"
    baseData["releases"][0]["tender"]["mainProcurementCategory"] = mainProcurementCategoriesMap.get(rawData["base"]["categoría"], "works")
    baseData["releases"][0]["tender"]["additionalProcurementCategories"] = [ rawData["base"]["categoría"] ]

    # Entidad compradora
    entity = {}
    entity["id"] = len(baseData["releases"][0]["parties"])
    entity["name"] = rawData["base"]["entidad"]
    entity["details"] = {
        "guatecompras_uri" : BASE_GTC_URI + rawData["base"]["html entidad"].find("a").get("href"),
        "type": rawData["base"]["tipo de entidad"] if "tipo de entidad" in rawData["base"] else "unknown",
        "unit": rawData["base"]["unidad compradora"] if "unidad compradora" in rawData["base"] else "unknown",
    }
    baseData["releases"][0]["tender"]["procuringEntity"] = \
        baseData["releases"][0]["buyer"] = \
            { "name": entity["name"], "id": entity["id"] }
    baseData["releases"][0]["parties"].append(entity)

    # status
    baseData["releases"][0]["tender"]["status"] = tenderStatusMap.get(rawData["base"]["estatus"], "")

    # procurement method
    if rawData["base"].get("tipo de proceso") == "Adquisición Competitiva":
        if rawData["base"].get("tipo de concurso") == "Público":
            baseData["releases"][0]["tender"]["procurementMethod"] = "open"
    baseData["releases"][0]["tender"]["procurementMethodDetails"] = "Modalidad: {}, Tipo de Proceso: {}, Tipo de concurso: {}".format(
        rawData["base"].get("modalidad", ""),
        rawData["base"].get("tipo proceso", ""),
        rawData["base"].get("tipo de concurso", "")
    )

    submethod = rawData["base"].get("recepción de ofertas", "")
    if submethod.startswith("Sólo en papel"):
        baseData["releases"][0]["tender"]["submissionMethod"] = "written"

    docsGlobalCounter = 1

    # Documents mapping
    if "documents" in rawData:
        for doc in rawData["documents"]:
            document = {
                "description": "",
                "language": "es",
                "id": docsGlobalCounter,
                "title": doc["type"],
                "url":  BASE_GTC_URI + doc["link"]
            }
            if  doc["type"] == "Anuncio, convocatoria o invitación":
                document["documentType"] = "tenderNotice"
                baseData["releases"][0]["tender"]["documents"].append(document)
            elif doc["type"] == "Bases, especificaciones generales o términos de referencia":
                document["documentType"] = "technicalSpecifications"
                baseData["releases"][0]["tender"]["documents"].append(document)
            elif doc["type"] == "Boleta de SNIP":
                document["documentType"] = "projectPlan"
                baseData["releases"][0]["planning"]["documents"].append(document)
            elif doc["type"] == "Criterio de calificación":
                document["documentType"] = "evaluationCriteria"
                baseData["releases"][0]["tender"]["documents"].append(document)
            elif doc["type"] == "Dictamen de aprobación de estudio de factibilidad":
                document["documentType"] = "x_feasibilityStudyAssessment"
                baseData["releases"][0]["planning"]["documents"].append(document)
            elif doc["type"] == "Dictamen de aprobación de impacto ambiental":
                document["documentType"] = "x_environmentalImpactAssessment"
                baseData["releases"][0]["planning"]["documents"].append(document)
            elif doc["type"] == "Dictamen técnico":
                document["documentType"] = "x_technicalAssessment"
                baseData["releases"][0]["planning"]["documents"].append(document)
            elif doc["type"] == "Diseño del Proyecto":
                document["documentType"] = "x_projectDesign"
                baseData["releases"][0]["planning"]["documents"].append(document)
            elif doc["type"] == "Estudio de Factibilidad":
                document["documentType"] = "feasibilityStudy"
                baseData["releases"][0]["planning"]["documents"].append(document)
            elif doc["type"] == "Estudio de impacto ambiental":
                document["documentType"] = "environmentalImpact"
                baseData["releases"][0]["planning"]["documents"].append(document)
            elif doc["type"] == "Estudios, diseños o planos":
                document["documentType"] = "x_otherStudies"
                baseData["releases"][0]["planning"]["documents"].append(document)
            elif doc["type"] == "Modelo de oferta (formulario)":
                document["documentType"] = "contractDraft"
                baseData["releases"][0]["tender"]["documents"].append(document)
            elif doc["type"] == "Opinión jurídica":
                document["documentType"] = "x_legalOpinion"
                baseData["releases"][0]["planning"]["documents"].append(document)
            elif doc["type"] == "Proyecto de bases":
                document["documentType"] = "procurementPlan"
                baseData["releases"][0]["planning"]["documents"].append(document)
            elif doc["type"] == "Resolución de aprobación de bases":
                document["documentType"] = "x_procurementApproval"
                baseData["releases"][0]["tender"]["documents"].append(document)
            elif doc["type"] == "Selección de supervisor de la obra":
                document["documentType"] = "x_supervisorSelection"
                baseData["releases"][0]["tender"]["documents"].append(document)
            elif doc["type"] == "Solicitud o requerimiento de bien, servicio o suministro":
                document["documentType"] = "needsAssessment"
                baseData["releases"][0]["planning"]["documents"].append(document)

            docsGlobalCounter += 1

    # Products types or items
    if "items" in rawData:
        baseData["releases"][0]["tender"]["items"].extend(rawData["items"])

    # bids
    if "bids" in rawData:
        for bid in rawData["bids"]:
            partyId = len(baseData["releases"][0]["parties"])
            baseData["releases"][0]["parties"].append({
                "id": partyId,
                "name":  bid["supplierName"],
                "details": {
                    "guatecompras_uri": BASE_GTC_URI + bid["supplierLink"]
                }
            })
            baseData["releases"][0]["bids"]["details"].append({
                "id": len(baseData["releases"][0]["bids"]["details"]),
                "status": None,
                "date": "",
                "tenderers": [{ "id": partyId, "name": bid["supplierName"] }],
                "value": {
                    "amount": float(bid["value"].replace(",", "")),
                    "currency": GTC_CURRENCY
                }
            })

    if "awards" in rawData:
        for award in rawData["awards"]:
            party =[p for p in baseData["releases"][0]["parties"] if p["name"] == award["supplierName"]]
            if len(party) == 1:
                baseData["releases"][0]["awards"].append({
                    "id": len(baseData["releases"][0]["awards"]),
                    "suppliers": [
                        { "id": party[0]["id"], "name": party[0]["name"] }
                    ],
                    "value": {
                        "amount": float(award["value"].replace(",", "")),
                        "currency": GTC_CURRENCY
                    }
                })

    # Contracts documents
    if "contractDocs" in rawData:
        for doc in rawData["contractDocs"]:
            if doc["fase"] == 1:
                baseData["releases"][0]["tender"]["documents"].append({
                    "id": docsGlobalCounter,
                    "documentType": "x_unknown",
                    "title": doc["name"],
                    "language": "es",
                    "url": BASE_GTC_URI + doc["link"]
                })
            elif doc["fase"] == 2:
                contractFound = [(i, c) for i, c in enumerate(baseData["releases"][0]["contracts"]) if c["id"] == doc["contractNumber"]]
                awardFound = [aw["id"] for aw in baseData["releases"][0]["awards"] if aw["suppliers"][0]["name"] == doc["supplierName"]]
                if len(contractFound) == 0:
                    baseData["releases"][0]["contracts"].append({
                        "id": doc["contractNumber"],
                        "awardID": awardFound[0] if len(awardFound) > 0 else None,
                        "value": {
                            "amount": float(doc["contractValue"].replace(",", "")),
                            "currency": GTC_CURRENCY
                        },
                        "documents": [{
                            "id": docsGlobalCounter,
                            "name": doc["link"],
                            "url": BASE_GTC_URI + doc["link"],
                            "language": "es",
                            "documentType": "x_unknown"
                        }]
                    })
                else:
                    baseData["releases"][0]["contracts"][contractFound[0][0]]["documents"].append({
                        "id": docsGlobalCounter,
                        "name": doc["link"],
                        "url": BASE_GTC_URI + doc["link"],
                        "language": "es",
                        "documentType": "x_unknown"
                    })
            docsGlobalCounter += 1

    return baseData


if __name__ == "__main__":
    print("Guatecompras scraper. Outputs OCDS record.\nUsage: guatecompras.py OCID NOG")
    ocid = sys.argv[1]
    nog = sys.argv[2]
    newRecord = GetEmptyOCDSRecord(ocid)
    rawData = FetchData(nog)
    newRecord = UpdateData(newRecord, rawData)
    print( json.dumps(newRecord, indent = 4, sort_keys = True, default=JSONSerializer ) )
