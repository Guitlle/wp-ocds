<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://guilles.website
 * @since      1.0.0
 *
 * @package    Wp_Ocds
 * @subpackage Wp_Ocds/admin/partials
 */
?>
<input type="hidden" name="wp_ocds_data" id="wp_ocds_data_input">
<div class="container" id="app">
    <!-- <select v-model="vmodel.sortBy">
        <option value="date">Ordenar por fecha</option>
    </select> | <button type="button" class="positive" @click="newRls"> +  Nuevo subregistro</button> -->
    <div v-if="vmodel.sortBy=='date'">
        <div class="ocds-release-editor" v-for="releaseIdx in sortRlsByDate(model.releases)" >
            <h3>
                <button type="button" class="positive" @click="toggleItem('rls-'+releaseIdx)">{{ vmodel.expandedItems["rls-"+releaseIdx]? "&uarr;": "&darr;"}}</button>
                OCDS Release - {{model.releases[releaseIdx].releases[0].id}}
                <br>
                <sub>{{model.releases[releaseIdx].releases[0].date}}</sub>
            </h3>
            <div class="ocds-release-editor-content" v-if="vmodel.expandedItems['rls-'+releaseIdx]">
                <div class="ocds-section-editor-content">
                    <h4 class="ocds-title-pull-down">Campos extra de Ojo Con Mi Pisto:</h4>
                    <div class="input-row"> <label>NOG: </label> <span class="input"><input  v-model="model.releases[releaseIdx].ocmp_extras.identification.NOG" > </span></div>
                    <div class="input-row"> <label>SNIP: </label> <span class="input"><input  v-model="model.releases[releaseIdx].ocmp_extras.identification.SNIP" > </span></div>
                    <div class="input-row"> <label>Departamento: </label> <span class="input"><input  v-model="model.releases[releaseIdx].ocmp_extras.location.department" > </span></div>
                    <div class="input-row"> <label>Municipio: </label> <span class="input"><input  v-model="model.releases[releaseIdx].ocmp_extras.location.municipality" > </span></div>
                    <div class="input-row"> <label>Coordenadas: </label> <div class="multiline-input">
                        Latitud <input  v-model="model.releases[releaseIdx].ocmp_extras.location.lat" > <br>
                        Longitud <input  v-model="model.releases[releaseIdx].ocmp_extras.location.lon" >
                    </div></div>
                    <hr>
                    <h4 class="ocds-title-pull-down">Release package information</h4>
                    <div class="input-row"> <label>URI: </label> <span class="input"><input v-model="model.releases[releaseIdx].uri" > </span></div>
                    <div class="input-row"> <label>Fecha de publicación: </label> <span class="input"><pikaday v-model="model.releases[releaseIdx].publishedDate"></pikaday></span></div>
                    <div class="input-row"> <label>Fecha de subregistro: </label> <span class="input"><pikaday v-model="model.releases[releaseIdx].releases[0].date"></pikaday></span></div>
                    <div class="input-row"> <label>URI: </label> <span class="input"><input v-model="model.releases[releaseIdx].uri" > </span></div>
                    <div class="input-row"> <label>OCID: </label> <span class="input"><input v-model="model.releases[releaseIdx].releases[0].ocid" > </span></div>
                    <div class="input-row"> <label>ID: </label> <span class="input"><input v-model="model.releases[releaseIdx].releases[0].id" > </span></div>
                    <div class="input-row"> <label>Tag (tipo): </label>
                        <div class="multiline-input">
                            <select v-model="model.releases[releaseIdx].releases[0].tag">
                                <option v-for="val in codes.tagsCodes" v-bind:value="[ val ]"> {{val}}</option>
                            </select> <br>
                            <sub><a target="_blank" href="http://standard.open-contracting.org/latest/en/schema/codelists/#release-tag">(guía)</a></sub><hr>
                        </div>
                    </div>
                    <div class="input-row"> <label>Comprador</label>
                        <div class="multiline-input">
                            <select height="2" v-model="model.releases[releaseIdx].releases[0].buyer">
                                <option v-for="party in model.releases[releaseIdx].releases[0].parties" v-bind:value="{id: party.id, name: party.name}"> {{party.id}} - {{party.name}}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <h3>Partes Interesadas <button type="button" class="positive" @click="insertObjectToList(model.releases[releaseIdx].releases[0].parties, 'organization')"> + </button> </h3>
                    <ocds-organization
                        v-for="(party, index) in model.releases[releaseIdx].releases[0].parties"
                        :value="party"
                        :index="index"
                        :key="releaseIdx+'-party-'+party.id"
                        @remove="removeObjectFromList(model.releases[releaseIdx].releases[0].parties , index)">
                    </ocds-organization>

                <h3 class="ocds-section-title">
                    <button type="button" v-bind:class="(!model.releases[releaseIdx].releases[0].planning)?'positive':'negative'" @click="toggleSection(releaseIdx, 'planning')">{{ (!model.releases[releaseIdx].releases[0].planning)? " + Habilitar": " - Deshabilitar"}}</button>
                    Planificación
                </h3>
                <div class="ocds-section-editor-content" v-if="model.releases[releaseIdx].releases[0].planning">
                    <div class="input-row"> <label>Justificación del proyecto: </label>
                        <div class="multiline-input">
                            <textarea height="5" v-model="model.releases[releaseIdx].releases[0].planning.rationale"></textarea>
                        </div>
                    </div>
                    <hr>
                    <h4>Presupuesto</h4>
                    <div class="input-row"> <label>Id: </label> <span class="input"><input v-model="model.releases[releaseIdx].releases[0].planning.budget.id" > </span></div>
                    <div class="input-row"> <label>Descripción: </label> <span class="input"><input v-model="model.releases[releaseIdx].releases[0].planning.budget.description" > </span></div>
                    <div class="input-row"> <label>Monto: </label>
                        <ocds-amount v-model="model.releases[releaseIdx].releases[0].planning.budget.amount"></ocds-amount>
                    </div>
                    <div class="input-row"> <label>Nombre del proyecto: </label> <span class="input"><input v-model="model.releases[releaseIdx].releases[0].planning.budget.project" > </span></div>
                    <div class="input-row"> <label>Id del proyecto: </label> <span class="input"><input v-model="model.releases[releaseIdx].releases[0].planning.budget.projectID" > </span></div>
                    <div class="input-row"> <label>URI del presupuesto: </label> <span class="input"><input v-model="model.releases[releaseIdx].releases[0].planning.budget.uri" > </span></div>
                    <hr>
                    <h4>Documentos sobre la planificación <button type="button" class="positive"  @click="insertObjectToList(model.releases[releaseIdx].releases[0].planning.documents, 'document')"> + Agregar documento</button></h4>
                    <ocds-document
                        v-for="(document, index) in model.releases[releaseIdx].releases[0].planning.documents"
                        :value="document"
                        :index="index"
                        :key="releaseIdx+'-plan-doc-'+document.id"
                        @remove="removeObjectFromList(document in model.releases[releaseIdx].releases[0].planning.documents, index)">
                    </ocds-document>
                    <hr>
                    <h4>Metas <button type="button" class="positive"  @click="insertObjectToList(model.releases[releaseIdx].releases[0].planning.milestones, 'milestone')"> + Agregar</button></h4>
                    <ocds-milestone
                        v-for="(milestone, index) in model.releases[releaseIdx].releases[0].planning.milestones"
                        :value="milestone"
                        :index="index"
                        :key="releaseIdx+'-plan-mlst-'+milestone.id"
                        @remove="removeObjectFromList(model.releases[releaseIdx].releases[0].planning.milestones, index)">
                    </ocds-milestone>
                </div>
                <h3 class="ocds-section-title">
                    <button type="button" v-bind:class="model.releases[releaseIdx].releases[0].tender==undefined?'positive':'negative'" @click="toggleSection(releaseIdx, 'tender')">{{ model.releases[releaseIdx].releases[0].tender==undefined? " + Habilitar": " - Deshabilitar"}}</button>
                    Licitación
                </h3>
                <div v-if="model.releases[releaseIdx].releases[0].tender!==undefined">
                    <div class="input-row"> <label>Id: </label> <span class="input"><input v-model="model.releases[releaseIdx].releases[0].tender.id" > </span></div>
                    <div class="input-row"> <label>Título: </label> <span class="input"><input  v-model="model.releases[releaseIdx].releases[0].tender.title" > </span></div>
                    <div class="input-row"> <label>Descripción: </label>
                        <div class="multiline-input">
                            <textarea height="5" v-model="model.releases[releaseIdx].releases[0].tender.description"></textarea>
                        </div>
                    </div>
                    <div class="input-row"> <label>Categoría principal: </label>
                        <div class="multiline-input">
                            <select v-model="model.releases[releaseIdx].releases[0].tender.mainProcurementCategory">
                                <option v-for="val in codes.mainProcurementCategories" v-bind:value="val"> {{val}}</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-row"> <label>Categoría secundaria: </label> <span class="input"><input  v-model="model.releases[releaseIdx].releases[0].tender.additadditionalProcurementCategories" > </span></div>
                    <div class="input-row"> <label>Status: </label>
                        <div class="multiline-input">
                            <select v-model="model.releases[releaseIdx].releases[0].tender.status">
                                <option v-for="val in codes.tenderStatus" v-bind:value="val"> {{val}}</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-row"> <label>Valor: </label>
                        <ocds-amount v-model="model.releases[releaseIdx].releases[0].tender.value"></ocds-amount>
                    </div>
                    <div class="input-row"> <label>Valor mínimo estimado: </label>
                        <ocds-amount v-model="model.releases[releaseIdx].releases[0].tender.minValue"></ocds-amount>
                    </div>
                    <div class="input-row"> <label>Procuring entity</label>
                        <div class="multiline-input">
                            <select height="2" v-model="model.releases[releaseIdx].releases[0].tender.procuringEntity">
                                <option v-for="party in model.releases[releaseIdx].releases[0].parties" v-bind:value="{id: party.id, name: party.name}"> {{party.id}} - {{party.name}}</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-row"> <label>Método de procuramiento: </label>
                        <div class="multiline-input">
                            <select v-model="model.releases[releaseIdx].releases[0].tender.procurementMethod">
                                <option v-for="val in codes.procurementMethods" v-bind:value="val"> {{val}}</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-row"> <label>Detalles del método <br> de procuramiento: </label>
                        <div class="multiline-input">
                            <textarea height="5" v-model="model.releases[releaseIdx].releases[0].tender.procurementMethodDetails"></textarea>
                        </div>
                    </div>
                    <div class="input-row"> <label>Justificación del método <br> de procuramiento: </label>
                        <div class="multiline-input">
                            <textarea height="5" v-model="model.releases[releaseIdx].releases[0].tender.procurementMethodRationale"></textarea>
                        </div>
                    </div>
                    <div class="input-row"> <label>Criterio de adjudicación: </label>
                        <div class="multiline-input">
                            <select v-model="model.releases[releaseIdx].releases[0].tender.awardCriteria">
                                <option v-for="val in codes.awardCriteria" v-bind:value="val"> {{val}}</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-row"> <label>Detalles del criterio de adjudicación: </label>
                        <div class="multiline-input">
                            <textarea height="5" v-model="model.releases[releaseIdx].releases[0].tender.awardCriteriaDetails"></textarea>
                        </div>
                    </div>
                    <div class="input-row"> <label>Criterios de eligibilidad: </label>
                        <div class="multiline-input">
                            <textarea height="5" v-model="model.releases[releaseIdx].releases[0].tender.awardCriteriaDetails"></textarea>
                        </div>
                    </div>
                    <div class="input-row"> <label>Método de participación: </label>
                        <div class="multiline-input">
                            <select multiple v-model="model.releases[releaseIdx].releases[0].tag">
                                <option v-for="val in codes.submissionMethods" v-bind:value="val"> {{val}}</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-row"> <label>Descripción del método de participación: </label> <span class="input"><input  v-model="model.releases[releaseIdx].releases[0].tender.submissionMethodDetails" > </span></div>
                    <div class="input-row">
                        <label>Período de tiempo de la licitación: </label>
                        <ocds-period v-model="model.releases[releaseIdx].releases[0].tender.tenderPeriod"></ocds-period>
                    </div>
                    <div class="input-row">
                        <label>Período de tiempo para consultas: </label>
                        <ocds-period v-model="model.releases[releaseIdx].releases[0].tender.enquiryPeriod"></ocds-period>
                    </div>
                    <div class="input-row">
                        <label>Tiene consultas (enquiries): </label>
                        <div class="multiline-input">
                            <select v-model="model.releases[releaseIdx].releases[0].tender.hasEnquiries">
                                <option v-for="(val, name, i) in {'Si': true, 'No': false}" v-bind:value="val"> {{name}}</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-row">
                        <label>Período de tiempo para adjudicar: </label>
                        <ocds-period v-model="model.releases[releaseIdx].releases[0].tender.awardPeriod"></ocds-period>
                    </div>
                    <div class="input-row">
                        <label>Período de tiempo de la contratación: </label>
                        <ocds-period v-model="model.releases[releaseIdx].releases[0].tender.contractPeriod"></ocds-period>
                    </div>
                    <div class="input-row"> <label>Ofertantes</label>
                        <div class="multiline-input">
                            <select v-if="model.releases[releaseIdx].releases[0].tender.tenderers != null" height="2" multiple v-model="model.releases[releaseIdx].releases[0].tender.tenderers">
                                <option v-for="party in model.releases[releaseIdx].releases[0].parties" v-bind:value="{id: party.id, name: party.name}"> {{party.id}} - {{party.name}}</option>
                            </select>
                            <div v-else>
                                <button type="button" class="neutral" @click="enableList(model.releases[releaseIdx].releases[0].tender, 'tenderers')">Habilitar</button>
                            </div>
                        </div>
                    </div>
                    <h4>Tipos de productos <button class="positive"  @click="insertObjectToList(model.releases[releaseIdx].releases[0].tender.items, 'item')"> + Agregar</button></h4>
                    <ocds-item
                        v-for="(item, index) in model.releases[releaseIdx].releases[0].tender.items"
                        :value="item"
                        :index="index"
                        :key="releaseIdx+'-tender-item-'+item.id"
                        @remove="removeObjectFromList(model.releases[releaseIdx].releases[0].tender.items, index)">
                    </ocds-item>
                </div>
                <h3 class="ocds-section-title">
                    Adjudicaciones &nbsp;&nbsp;&nbsp;  <button type="button" class="positive" @click="ensureInsertObjectToList(model.releases[releaseIdx].releases[0], 'awards', 'award')"> + Agregar</button>
                </h3>
                <div class="ocds-awards-container" v-if="model.releases[releaseIdx].releases[0].awards!==undefined">
                    <ocds-award
                        v-for="(award, index) in model.releases[releaseIdx].releases[0].awards"
                        :value="award"
                        :index="index"
                        :key="releaseIdx+'-award-'+award.id"
                        @remove="removeObjectFromList(model.releases[releaseIdx].releases[0].awards, index)"
                        :parties="model.releases[releaseIdx].releases[0].parties">
                    </ocds-award>
                </div>
                <h3 class="ocds-section-title">
                    Contratos &nbsp;&nbsp;&nbsp;  <button type="button" class="positive" @click="ensureInsertObjectToList(model.releases[releaseIdx].releases[0], 'contracts', 'contract')"> + Agregar</button>
                </h3>
                <div class="ocds-contract-container" v-if="model.releases[releaseIdx].releases[0].contracts!==undefined">
                    <ocds-contract
                        v-for="(contract, index) in model.releases[releaseIdx].releases[0].contracts"
                        :value="contract"
                        :index="index"
                        :key="releaseIdx+'-contract-'+contract.id"
                        @remove="removeObjectFromList(model.releases[releaseIdx].releases[0].contracts, index)"
                        :parties="model.releases[releaseIdx].releases[0].parties"
                        :awards="model.releases[releaseIdx].releases[0].awards">
                    </ocds-contract>
                </div>
                <div class="input-row"> <label></label><span class="input"> <button type="button" class="negative" @click="toggleItem('rls-'+releaseIdx)">Cerrar editor de subregistro &uarr; </button> </span></div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">

var data, post_id = "<?php echo get_the_ID(); ?>";
data = <?php
$json_data = get_post_meta(get_the_ID(), "wp-ocds-record-data", "{}");
if (substr($json_data, 0,1) == "{" AND is_object(json_decode($json_data))) {
    echo $json_data;
}
else {
    echo "null";
}
?>;

/* malformed data: */
if (data && !data.releases) {
    data = null;
}

var vI = init_single_editor(data, post_id);

document.getElementById("post").onsubmit = function (e) {
    document.getElementById("wp_ocds_data_input").value = JSON.stringify(vI.$data.model.releases[0]);
    console.log(document.getElementById("wp_ocds_data_input").value);
    return true;
}

</script>
