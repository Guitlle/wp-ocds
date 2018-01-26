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

now = datetime.datetime.now()

BASE_GTC_URI = "http://www.guatecompras.gt"

OCDSConfig = {
    "uriPrefix": "http://www.ojoconmipisto.com/open-contracting/",
    "publisher": {
        "name": "Ojo con mi pisto",
        "scheme": None,
        "uid": "null",
        "uri": "http://www.ojoconmipisto.com"
    },
    "license": "http://opendatacommons.org/licenses/pddl/1.0/",
    "publishedDate": now.isoformat(),
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

            },
            "planning": {

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
            "https://raw.githubusercontent.com/Guitlle/wp-ocds/ocmp_extension/schema.json"
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

def FetchMainGTCRecord(NOG):
    # Obtener página principal
    main = requests.get("http://guatecompras.gt/concursos/consultaConcurso.aspx?nog={}&o=5".format(NOG))
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
    return details_data
    # Obtener las vistas parciales de las pestañas inferiores
    """
    formData = {
        "__VIEWSTATE" : main_soup.find(id = "__VIEWSTATE").get("value"),
        "__VIEWSTATEGENERATOR" : main_soup.find( id = "__VIEWSTATEGENERATOR").get("value"),
        "MasterGC$ContentBlockHolder$scriptManager" : "MasterGC$ContentBlockHolder$ctl01|MasterGC$ContentBlockHolder$RadTabStrip1",
        "MasterGC$svrID" : "4",
        "MasterGC_ContentBlockHolder_RadTabStrip1_ClientState" : '{"selectedIndexes":["1"],"logEntries":[],"scrollState":{}}',
        "MasterGC_ContentBlockHolder_RMP_Historia_ClientState" : "",
        "__EVENTTARGET": "MasterGC$ContentBlockHolder$RadTabStrip1",
        "__EVENTARGUMENT": '{"type":0,"index":"0"}',
        "__ASYNCPOST": "true"
    };
    tab1 = requests.post("http://guatecompras.gt/concursos/consultaConcurso.aspx?nog={}&o=5".format(NOG), data = formData)
    """

def UpdateData(baseData, details_data):
    # Assume there is a NOG id
    baseData["releases"][0]["ocmp_extras"]["identification"]["NOG"] = details_data["nog"]

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

    return baseData


if __name__ == "__main__":
    print("Guatecompras scraper. Outputs OCDS record.\nUsage: guatecompras.py OCID NOG")
    ocid = sys.argv[1]
    nog = sys.argv[2]
    newRecord = getEmptyOCDSRecord(ocid)
    mainData = FetchMainGTCRecord(nog)
    newRecord = UpdateData(newRecord, mainData)
    print( json.dumps(newRecord, indent = 4, sort_keys = True, default=json_serializer ) )
