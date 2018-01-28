#!/usr/bin/env python3.5
# -*- coding: utf-8 -*-
import json
import guatecompras
import pandas as pd
import re

obras = pd.read_csv("Listado de Obras Bajo la Lupa.csv")

def ParseUglyDate2(dateText):
    try:
        extract = re.match("(\d*)\/(\d*)\/(\d*)", dateText.strip())
        if extract is not None:
            data = extract.groups()
            return datetime.date(data[2], data[0], data[1])
    except:
        pass
    return None

for i, row in obras.iterrows():
    if pd.isna(row["NO"]): break
    print(row)
    nog = row["NOG"].strip()
    newRecord = guatecompras.GetEmptyOCDSRecord(nog)
    rawData   = guatecompras.FetchData(nog)
    newRecord = guatecompras.UpdateData(newRecord, rawData)

    newRecord["releases"][0]["ocmp_extras"]["location"]["municipality"] = row["MUNICIPALIDAD"].strip()
    newRecord["releases"][0]["ocmp_extras"]["location"]["lat"]          = row["LATITUD"]
    newRecord["releases"][0]["ocmp_extras"]["location"]["lon"]          = row["LONGITUD"]
    newRecord["releases"][0]["ocmp_extras"]["progress"]["financial"]    = float(row["AVANCE FINANCIERO"].replace("%", ""))
    newRecord["releases"][0]["ocmp_extras"]["progress"]["physical"]     = float(row["AVANCE FÍSICO"].replace("%", ""))
    if len(newRecord["releases"][0]["contracts"]) == 0:
        newRecord["releases"][0]["contracts"].append( {
            "id": 0,
        })
    newRecord["releases"][0]["contracts"][0]["period"] = {
        "startDate" : ParseUglyDate2(row["FECHA INICIO DE PROYECTO"].strip()),
        "endDate"   : ParseUglyDate2(row["FECHA FINAL DEL PROYECTO"].strip())
    }

    outFile = open("output/OCDS-NOG-"+nog+".json", "w")
    json.dump(newRecord, outFile, indent = 4, sort_keys = True, default=guatecompras.JSONSerializer)
    outFile.close()
