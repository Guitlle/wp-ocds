<?php
/**
 * Vue.js templates for the editor.
 */
?>
<script  type="text/x-template" id="vtemplate-ocds-period">
    <div class="ocds-field-editor ocds-fields-group">
        <template v-if="period != null">
            Comienzo:
            <pikaday v-model="period.startDate"></pikaday>
            Final:
            <pikaday v-model="period.endDate"></pikaday>
        </template>
        <template v-else>
            <button type="button" class="neutral" @click="setDefault()" >Activar campo</button>
        </template>
    </div>

</script>
<script  type="text/x-template" id="vtemplate-ocds-amount">
    <div class="amount-field ocds-field-editor">
        <div v-if="amount != null">
            <span class="input-pre">
                <select v-model="amount.currency" >
                    <option v-for="val in codes.currencies" v-bind:value="val" >{{val}}</option>
                </select>
            </span>
            <span class="input-post"><input v-model="amount.amount" > </span>
            <button type="button" class="negative" @click="disableField">Desactivar</button>
        </div>
        <div v-else >
            <button type="button" class="neutral" @click="enableField">Ingresar cantidad</button>
        </div>
    </div>
</script>
<script  type="text/x-template" id="vtemplate-ocds-item">
    <div class="ocds-item ocds-item-editor  ocds-object-editor">
        <h4 class="ocds-item-title ocds-object-title">
            <button type="button" class="positive" @click="show=!show">{{ show? "&uarr;": "&darr;"}} </button>
            <button type="button" style="float: right;" class="negative" @click="removeObject"> Delete </button>
            {{item.id}}
        </h4>
        <div class="ocds-item-editor-content ocds-object-editor-content" v-if="show">
            <div class="input-row"> <label>Local Id: </label> <span class="input"><input v-model.lazy="item.id" > </span></div>
            <div class="input-row"> <label>Descripción: </label> <span class="input"><input v-model="item.description" > </span></div>
            <div class="input-row"> <label>Cantidad: </label> <span class="input"><input v-model="item.quantity" > </span></div>
            <div class="input-row"> <label>Clasificación: </label> <span class="input"><input v-model="item.classification.description"> </span></div>
            <div class="input-row"> <label>Unidad de medida: </label> <span class="input"><input v-model="item.unit.name"> </span></div>
            <div class="input-row"> <label>Valor de unidad de medida: </label> <ocds-amount v-model="item.unit.value"></ocds-amount></div>
        </div>
    </div>
</script>
<script  type="text/x-template" id="vtemplate-ocds-organization">
    <div class="ocds-organization ocds-organization-editor ocds-object-editor" >
        <h4 class="ocds-organization-title  ocds-object-title">
            <button type="button" class="positive" @click="show=!show">{{ show? "&uarr;": "&darr;"}} </button>
            <button type="button" style="float: right;" class="negative" @click="removeObject"> Delete </button>
            {{party.name}}
        </h4>
        <div class="ocds-organization-editor-content" v-if="show">
            <div class="input-row"> <label>Local Id: </label> <span class="input"><input v-model.lazy="party.id" > </span></div>
            <div class="input-row"> <label>Name: </label>  <span class="input"><input v-model="party.name" > </span></div>
            <div class="input-row"> <label>Roles: </label>
                <div class="multiline-input">
                    <select multiple v-model="party.roles">
                        <option v-for="(name, val) in codes.partyRoles" v-bind:value="val"> {{name}}</option>
                    </select> <br>
                    <sub><a target="_blank" href="http://standard.open-contracting.org/latest/en/schema/codelists/#party-role">(guía)</a></sub>
                </div>
            </div>
            <hr>
            <h5> Identificación &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <button v-if="!party.identifier" type="button" class="positive" @click="enableProperty(party, 'identifier')"> + Habilitar</button></h5>
            <template v-if="party.identifier">
                <div class="input-row"> <label> Id: </label> <span class="input"><input v-model="party.identifier.id" > </span></div>
                <div class="input-row"> <label> Legal Name: </label> <span class="input"><input v-model="party.identifier.legalName" > </span></div>
                <div class="input-row"> <label> URI: </label> <span class="input"><input v-model="party.identifier.uri" > </span></div>
                <div class="input-row"> <label> Scheme: </label> <span class="input"><input v-model="party.identifier.scheme" > </span></div>
                <hr>
            </template>
            <h5>Dirección  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <button v-if="!party.address" type="button" class="positive" @click="enableProperty(party, 'address')"> + Habilitar</button></h5>
            <template v-if="party.address">
                <div class="input-row"> <label>Dirección: </label> <span class="input"><input v-model="party.address.streetAddress" > </span></div>
                <div class="input-row"> <label>Localidad (Municipio): </label> <span class="input"><input v-model="party.address.locality" > </span></div>
                <div class="input-row"> <label>Región (Departamento): </label> <span class="input"><input v-model="party.address.region" > </span></div>
                <div class="input-row"> <label>Código Postal: </label> <span class="input"><input v-model="party.address.postalCode" > </span></div>
                <div class="input-row"> <label>País: </label> <span class="input"><input v-model="party.address.countryName" > </span></div>
                <hr>
            </template>
            <h5>Punto de Contacto  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <button v-if="!party.contactPoint" type="button" class="positive" @click="enableProperty(party, 'contactPoint')"> + Habilitar</button> <br><sub>(Unidad encargada del proceso de compra)</sub> </h5>
            <template v-if="party.contactPoint">
                <div class="input-row"> <label>Nombre: </label> <span class="input"><input v-model="party.contactPoint.name" > </span></div>
                <div class="input-row"> <label>E-mail: </label> <span class="input"><input v-model="party.contactPoint.email" > </span></div>
                <div class="input-row"> <label>Teléfono: </label> <span class="input"><input v-model="party.contactPoint.telephone" > </span></div>
                <div class="input-row"> <label>Fax: </label> <span class="input"><input v-model="party.contactPoint.faxNumber" > </span></div>
                <div class="input-row"> <label>Sitio Web: </label>
                <span class="input"><input v-model="party.contactPoint.url" > </span></div>
                <hr>
            </template>
            <div class="input-row"> <label></label><span class="input"> <button class="negative" @click="show=false">Cerrar Sección &uarr; </button> </span></div>
        </div>
    </div>
</script>
<script  type="text/x-template" id="vtemplate-ocds-document">
    <div class="ocds-object-editor ocds-document">
        <h4 class="ocds-object-title">
            <button type="button" class="positive" @click="show=!show">{{ show? "&uarr;": "&darr;"}}</button>
            <button type="button" style="float: right;" class="negative" @click="removeObject"> Delete </button>
            {{document.id}} - {{document.title}}
            <br>
            <sub>{{document.datePublished}}</sub>
        </h4>
        <div class="ocds-object-editor-content" v-if="show">
            <div class="input-row"> <label>Id: </label> <span class="input"><input v-model.lazy="document.id" > </span></div>
            <div class="input-row"> <label>Título: </label> <span class="input"><input v-model="document.title" > </span></div>
            <div class="input-row"> <label>Descripción: </label> <span class="input"><input v-model.lazy="document.description" > </span></div>
            <div class="input-row"> <label>Tipo de documento: </label> <span class="input"><input v-model="document.documentType" > </span></div>
            <div class="input-row"> <label>URL: </label> <span class="input"><input v-model.lazy="document.url" > </span></div>
            <div class="input-row"> <label>Fecha de publicación: </label> <span  class="input"><pikaday v-model="document.datePublished"></pikaday></span></div>
            <div class="input-row"> <label>Formato: </label> <span class="input"><input v-model.lazy="document.format" > </span></div>
            <div class="input-row"> <label>Lenguaje: </label>
                <span class="input"><select v-model="document.language" >
                    <option v-bind:value="lang" v-for="lang in codes.languages">{{lang}}</option>
                </select> </span>
            </div>
        </div>
    </div>
</script>
<script type="text/x-template" id="vtemplate-ocds-milestone">
    <div class="ocds-object-editor ocds-milestone" >
        <h4 class="ocds-object-title">
            <button type="button" class="positive" @click="show=!show">{{ show? "&uarr;": "&darr;"}}</button>
            <button type="button" style="float: right;" class="negative" @click="removeObject"> Delete </button>
            {{milestone.id}} - {{milestone.title}}
            <br>
            <sub> Type: {{milestone.type}}, Status: {{milestone.status}}</sub>
        </h4>
        <div class="ocds-object-editor-content" v-if="show">
            <div class="input-row"> <label>Id: </label> <span class="input"><input v-model.lazy="milestone.id" > </span></div>
            <div class="input-row"> <label>Tipo: </label>
                <div class="multiline-input">
                    <select v-model="milestone.type">
                        <option v-for="val in codes.milestoneTypeCodes" v-bind:value="val"> {{val}}</option>
                    </select> <br>
                    <sub><a target="_blank" href="http://standard.open-contracting.org/latest/en/schema/codelists/#milestone-type">(guía)</a></sub><hr>
                </div>
            </div>
            <div class="input-row"> <label>Title: </label> <span class="input"><input v-model="milestone.title" > </span></div>
            <div class="input-row"> <label>Description: </label> <span class="input"><input v-model="milestone.description" > </span></div>
            <div class="input-row"> <label>Status: </label>
                <div class="multiline-input">
                    <select v-model="milestone.status">
                        <option v-for="val in codes.milestoneStatus" v-bind:value="val"> {{val}}</option>
                    </select>
                </div>
            </div>>
            <div class="input-row"> <label>Fecha planificada: </label> <span class="input"><pikaday v-model="milestone.dueDate"></pikaday></span></div>
            <div class="input-row"> <label>Fecha en que se cumple: </label> <span class="input"><pikaday v-model="milestone.dateMet"></pikaday></span></div>
        </div>
    </div>
</script>
<script  type="text/x-template" id="vtemplate-ocds-transaction">
    <div class="ocds-object-editor ocds-transaction">
        <h4 class="ocds-object-title">
            <button type="button" class="positive" @click="show=!show">{{ show? "&uarr;": "&darr;"}}</button>
            <button type="button" style="float: right;" class="negative" @click="removeObject"> Delete </button>
            [ {{transaction.id}} ] {{transaction.value ? transaction.value.currency : "GTQ" }} {{transaction.value? transaction.value.amount : "0.00"}}
            <br>
            <sub>{{transaction.date}}</sub>
        </h4>
        <div class="ocds-object-editor-content" v-if="show">
            <div class="input-row"> <label>Id: </label> <span class="input"><input v-model.lazy="transaction.id" > </span></div>
            <div class="input-row"> <label>Fuente de información: </label> <span class="input"><input v-model="transaction.source" > </span></div>
            <div class="input-row"> <label>URI de registro: </label> <span class="input"><input v-model="transaction.uri" > </span></div>
            <div class="input-row"> <label>Fecha: </label> <span  class="input"><pikaday v-model="transaction.date"></pikaday></span></div>
            <div class="input-row"> <label>Monto: </label> <ocds-amount v-model="transaction.value"></ocds-amount></div>
            <div class="input-row"> <label>Emisor del pago: </label>
                <div class="multiline-input">
                    <select v-model="transaction.payer">
                        <option v-for="party in parties" v-bind:value="{id: party.id, name: party.name}"> {{party.id}} - {{party.name}}</option>
                    </select>
                </div>
            </div>
            <div class="input-row"> <label>Receptor del pago: </label>
                <div class="multiline-input">
                    <select v-model="transaction.payee">
                        <option v-for="party in parties" v-bind:value="{id: party.id, name: party.name}"> {{party.id}} - {{party.name}}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</script>
<script type="text/x-template" id="vtemplate-ocds-award">
    <div class="ocds-object-editor ocds-award" >
        <h4 class="ocds-object-title">
            <button type="button" class="positive" @click="show=!show">{{ show? "&uarr;": "&darr;"}}</button>
            <button type="button" style="float: right;" class="negative" @click="removeObject"> Delete </button>
            {{award.id}} - {{award.title}}
            <br>
            <sub> Fecha: {{award.date}}, Status: {{award.status}}</sub>
        </h4>
        <div class="ocds-object-editor-content" v-if="show">
            <div class="input-row"> <label>Id: </label> <span class="input"><input v-model.lazy="award.id" > </span></div>
            <div class="input-row"> <label>Title: </label> <span class="input"><input v-focus v-model="award.title" > </span></div>
            <div class="input-row"> <label>Description: </label> <span class="input"><input v-focus v-model="award.description" > </span></div>
            <div class="input-row"> <label>Status: </label>
                <div class="multiline-input">
                    <select v-model="award.status">
                        <option v-for="val in codes.awardStatus" v-bind:value="val"> {{val}}</option>
                    </select>
                </div>
            </div>
            <div class="input-row"> <label>Fecha de adjudicación: </label> <span  class="input"><pikaday v-model="award.date"></pikaday></span></div>
            <div class="input-row"> <label>Monto adjudicado: </label> <ocds-amount v-model="award.value"></ocds-amount></div>
            <div class="input-row"> <label>Proveedores: </label>
                <div class="multiline-input">
                    <select height="2" multiple v-model="award.suppliers">
                        <option v-for="party in parties" v-bind:value="{id: party.id, name: party.name}"> {{party.id}} - {{party.name}}</option>
                    </select>
                </div>
            </div>
            <div class="input-row">
                <label>Período de tiempo del contrato adjudicado: </label>
                <ocds-period v-model="award.contractPeriod"></ocds-period>
            </div>
            <h4>Tipos de productos <button type="button" class="positive"  @click="ensureInsertObjectToList(award, 'items', 'item')"> + Agregar</button></h4>
            <ocds-item
                v-for="(item, index) in award.items"
                :value="item"
                :index="index"
                :key="'award-items-'+'-'+item.id"
                @remove="removeObjectFromList(award.items, index)">
            </ocds-item>
            <hr>
            <h4>Documentos de la adjudicación <button type="button" class="positive"  @click="ensureInsertObjectToList(award, 'documents', 'document')"> + Agregar documento</button></h4>
            <ocds-document
                v-for="(document, index) in award.documents"
                :value="document"
                :index="index"
                :key="'award-docs-'+document.id"
                @remove="removeObjectFromList(award.documents, index)">
            </ocds-document>
        </div>
    </div>
</script>
<script type="text/x-template" id="vtemplate-ocds-contract">
    <div class="ocds-object-editor ocds-contract" >
        <h4 class="ocds-object-title">
            <button type="button" class="positive" @click="show=!show">{{ show? "&uarr;": "&darr;"}}</button>
            <button type="button" style="float: right;" class="negative" @click="removeObject"> Delete </button>
            {{contract.id}} - {{contract.title}}
            <br>
            <sub> Status: {{contract.status}}</sub>
        </h4>
        <div class="ocds-object-editor-content" v-if="show">
            <div class="input-row"> <label>Id: </label> <span class="input"><input v-model.lazy="contract.id" > </span></div>
            <div class="input-row"> <label>Adjudicación relacionada</label>
                <div class="multiline-input">
                    <select  v-model="contract.awardID">
                        <option v-if="awards" v-for="award in awards" v-bind:value="award.id"> {{award.id}} - {{award.title}}</option>
                    </select>
                </div>
            </div>
            <div class="input-row"> <label>Title: </label> <span class="input"><input v-focus v-model="contract.title" > </span></div>
            <div class="input-row"> <label>Description: </label> <span class="input"><input v-focus v-model="contract.description" > </span></div>
            <div class="input-row"> <label>Status: </label>
                <div class="multiline-input">
                    <select v-model="contract.status">
                        <option v-for="val in codes.contractStatus" v-bind:value="val"> {{val}}</option>
                    </select>
                </div>
            </div>
            <div class="input-row">
                <label>Período de tiempo del contrato: </label>
                <ocds-period v-model="contract.period"></ocds-period>
            </div>
            <div class="input-row"> <label>Fecha en que se firmó: </label> <span  class="input"><pikaday v-model="contract.dateSigned"></pikaday></span></div>
            <div class="input-row"> <label>Monto adjudicado: </label> <ocds-amount v-model="contract.value"></ocds-amount></div>
            <div class="input-row">
                <label>Período de tiempo del contrato adjudicado: </label>
                <ocds-period v-model="contract.contractPeriod"></ocds-period>
            </div>
            <h4>Tipos de productos <button type="button" class="positive"  @click="ensureInsertObjectToList(contract, 'items', 'item')"> + Agregar</button></h4>
            <ocds-item
                v-for="(item, index) in contract.items"
                :value="item"
                :index="index"
                :key="'contract-items-'+'-'+item.id"
                @remove="removeObjectFromList(contract.items, index)">
            </ocds-item>
            <hr>
            <h4>Documentos generales del contrato <button type="button" class="positive"  @click="ensureInsertObjectToList(contract, 'documents', 'document')"> + Agregar documento</button></h4>
            <ocds-document
                v-for="(document, index) in contract.documents"
                :value="document"
                :index="index"
                :key="'contract-docs-'+document.id"
                @remove="removeObjectFromList(contract.documents, index)">
            </ocds-document>
            <hr>
            <h4>Metas del contrato <button type="button" class="positive"  @click="ensureInsertObjectToList(contract, 'milestones', 'document')"> + Agregar meta</button></h4>
            <ocds-milestone
                v-for="(milestone, index) in contract.milestones"
                :value="milestone"
                :index="index"
                :key="'contract-mlst-'+milestone.id"
                @remove="removeObjectFromList(contract.milestones, index)">
            </ocds-milestone>
            <hr>
            <h4>Información sobre implementación &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <button v-if="!contract.implementation" type="button" class="positive" @click="enableProperty(contract, 'implementation')"> + Habilitar</button></h4>
            <template v-if="contract.implementation">
                <h4>Transacciones  <button type="button" class="positive"  @click="ensureInsertObjectToList(contract.implementation, 'transactions', 'transaction')"> + Agregar transacción</button></h4>
                    <ocds-transaction
                        v-for="(transaction, index) in contract.implementation.transactions"
                        :value="transaction"
                        :index="index"
                        :key="'contract-tx-'+transaction.id"
                        :parties="parties"
                        @remove="removeObjectFromList(contract.implementation.transactions, index)">
                    </ocds-transaction>
                <hr>
                <h4>Metas completadas del contrato </h4>
                <ocds-milestone
                    v-for="(milestone, index) in contract.implementation.milestones"
                    :value="milestone"
                    :index="index"
                    :key="'contract-cmlst-'+milestone.id"
                    @remove="removeObjectFromList(contract.implementation.milestones, index)">
                </ocds-milestone>
                <hr>
                <h4>Documentos sobre la implementación del contrato <button type="button" class="positive"  @click="ensureInsertObjectToList(contract.implementation, 'documents', 'document')"> + Agregar documento</button></h4>
                <ocds-document
                    v-for="(document, index) in contract.implementation.documents"
                    :value="document"
                    :index="index"
                    :key="'contract-idocs-'+document.id"
                    @remove="removeObjectFromList(contract.implementation.documents, index)">
                </ocds-document>
            </template>
        </div>
    </div>
</script>
<script type="text/x-template" id="vtemplate-pikaday">
    <div class="pikaday">
        <input type="text" v-bind:value="dateStr" v-bind:style="{display: dateObj? 'inline': 'none', width: '15em'}" v-on:input="updateDate($event.target.value)">
        <template v-if="dateObj != null">
            <button type="button" class="negative" @click="disableField()">Desactivar</button>
            <template v-if="handleTime">
                <hr>
                Hora: <select :value="dateObj.getUTCHours()" @change.passive="setHour($event.target.value)"><option v-for="h in _.range(0, 24)">{{h}}</option></select> <strong>:</strong>
                <select :value="dateObj.getUTCMinutes()" @change.passive="setMinute($event.target.value)"><option v-for="h in _.range(0, 60)">{{h}}</option></select>
            </template>
        </template>
        <template v-else>
            <button type="button" class="neutral" @click="enableField">Ingresar Fecha</button>
        </template>
    </div>
</script>
