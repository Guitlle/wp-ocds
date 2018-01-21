/**
 * Author: Guillermo Ambrosio <yo@guilles.website>
 * Date: 2017/12/29
 *
 * OCDS editor
 *
 **/

/*  Integrate pikaday with vue and add hour/minute picker. */
Vue.component("pikaday", {
    template: "#vtemplate-pikaday",
    props: ["value", "handleTime"],
    data: function () {
        var date;
        if (this.value == null) {
            date = null;
        }
        else {
            try {
                date = new moment(this.value);
                date.seconds(0);
                date.milliseconds(0);
            }
            catch(e) {
                date = null;
            }
        }
        return {
            dateObj: date,
            picker: null
        };
    },
    computed: {
        dateStr: function () {
            return this.dateObj? this.dateObj.format("YYYY-MM-DD") : "";
        }
    },
    methods: {
        update: function() {
            this.$emit("input", this.dateObj? this.dateObj.toISOString() : null);
        },
        updateDate: function (val) {
            var onlydate = new moment(val);
            this.dateObj.year(onlydate.year());
            this.dateObj.month(onlydate.month());
            this.dateObj.date(onlydate.date());
            this.update();
        },
        setHour: function (hour) {
            this.dateObj.hours(hour);
            this.update();
        },
        setMinute: function (minute) {
            this.dateObj.minutes(minute);
            this.update();
        },
        enableField: function () {
            this.dateObj = new moment();
            this.update();
            this.initPicker();
        },
        disableField: function () {
            this.picker.destroy();
            this.picker = null;
            this.dateObj = null;
            this.update();
        },
        initPicker: function () {
            var that = this;
            var fieldElement = this.$el.querySelector("input")
            this.picker = new Pikaday({
                field: fieldElement,
                format: "YYYY-MM-DD",
                onSelect: function () {
                    that.updateDate(fieldElement.value)
                }
            });
        }
    },
    mounted: function () {
        if (this.dateObj != null) {
            this.initPicker();
        }
    },
    beforeDestroyed: function () {
        if (this.picker != null)
            this.picker.destroy();
    }
});
/* OCDS configuration namespace */
var OCDSConfig = {
    "uriPrefix": "http://www.ojoconmipisto.com/open-contracting/",
    "publisher": {
        "name": "Ojo con mi pisto",
        "scheme": null,
        "uid": "null",
        "uri": "http://www.ojoconmipisto.com"
    },
    "license": "http://opendatacommons.org/licenses/pddl/1.0/",
    "publishedDate": (new Date()).toISOString(),
    "publicationPolicy": ""
}
// OCDS namespace
var OCDS = {
    GetEmptyDocument: function (id) {
        var today = new moment();
        return {
            "version": OCDS.version,
            "uri": OCDSConfig.uriPrefix + "/ocds-" + id + ".json",
            "publisher": OCDSConfig.publisher,
            "publishedDate": OCDSConfig.publishedDate,
            "license": OCDSConfig.license,
            "publicationPolicy": OCDSConfig.publicationPolicy,
            "releases": [{
                "language": "es",
                "ocid": id,
                "id": id,
                "date": OCDSConfig.publishedDate,
                "tag": ["tender"],
                "parties": [],
                "tender": undefined,
                "planning": undefined,
                "awards": [],
                "contracts": [],
                "ocmp_extras": undefined
            }],
            "extensions":[
                /* TODO put the schema in this github url */
                "https://raw.githubusercontent.com/Guitlle/wp-ocds/ocmp_extension/schema.json"
            ],
        };
    },
    version: "1.1",
    defaults: {
        ocmp_extras: {
            "year": 2000,
            "location": { "lat": 0, "lon": 0, "department": "Guatemala", "municipality": "Guatemala" },
            "identification": {
                "NOG": "",
                "SNIP": ""
            },
            "progress": {
                "financial": 0,
                "physical": 0
            }
        },
        planning: {
            "budget": {
                "id": ""
            },
        },
        organization: {
            "identifier": {
                "scheme": "",
                "id": "",
                "legalName": "",
                "uri": ""
            },
            "name": "Nueva parte",
            "address": {
                "streetAddress": "",
                "locality": "",
                "region": "Guatemala",
                "postalCode": "",
                "countryName": "Guatemala"
            },
            "contactPoint": {
                "name": "",
                "email": "",
                "telephone": "+502 ",
                "faxNumber": "+502 ",
                "url": ""
            },
            "roles": [],
            "id": ""
        },
        document: {
            "id": "",
            "type": "",
            "title": "",
            "description": "",
            "status": "",
            "dueDate": ""
        },
        milestone: {
            "id": "",
            "title": "",
            "type": "",
            "description": "",
            "code": "",
            "dueDate": "",
            "dateMet": "",
            /*
                * leaving dateModified out, since we don't handle versioning or fragmented records
                */
            "status": ""
        },
        item: {
            "id": null,
            "description": null,
            "classification": {
                "description": null
            },
            "quantity": 0,
            "unit": {
                "name": null,
                "value": {}
            }
        },
        award: {
            "id": "",
            "title": "",
            "description": "",
            "status": "pending",
            "date": null,
            "value": null,
            "suppliers": [],
            "items": [],
            "contractPeriod": null,
            "documents": []
        },
        contract: {
            "id": "",
            "awardID": "",
            "title": "",
            "description": "",
            "status": "pending",
            "period": null,
            "value": null,
            "items": [],
            "dateSigned": "",
            "documents": [],
            "implementation": {
                "transactions": [],
                "milestones": [],
                "documents": []
            },
            "milestones": []
        },
        transaction: {
            "id": "",
            "source": "",
            "date": "",
            "payer": null,
            "payee": null,
            "uri": "",
            "value": null
        }
    },
    codes: {
        partyRoles: {
            buyer: "Buyer",
            procuringEntity: "Procuring Entity",
            supplier: "Supplier",
            tenderer: "Tenderer",
            funder: "Funder",
            enquirer: "Enquirer",
            payer: "Payer",
            payee: "Payee",
            reviewBody: "Review Body"
        },
        tagsCodes: [
            "planning",
            "planningUpdate",
            "tender",
            "tenderAmendment",
            "tenderUpdate",
            "tenderCancellation",
            "award",
            "awardUpdate",
            "awardCancellation",
            "contract",
            "contractUpdate",
            "contractAmendment",
            "implementation",
            "implementationUpdate",
            "contractTermination"
        ],
        currencies: [
            "GTQ",
            "USD"
        ],
        languages: [
            "es", "en", "otro"
        ],
        milestoneTypeCodes: [
            "preProcurement",
            "approval",
            "engagement",
            "assessment",
            "delivery",
            "reporting",
            "financing"
        ],
        milestoneStatus: [
            "scheduled",
            "met",
            "notMet",
            "partiallyMet"
        ],
        mainProcurementCategories: [
            "goods", "works","services"
        ],
        tenderStatus: [
            "planning", "planned", "active", "cancelled", "unsuccessful", "complete", "withdrawn"
        ],
        procurementMethods: [ "open", "selective", "limited", "direct"],
        awardCriteria: [
            "priceOnly",
            "costOnly",
            "qualityOnly",
            "ratedCriteria",
            "lowestCost",
            "bestProposal",
            "bestValueToGovernment",
            "singleBidOnly"
        ],
        submissionMethods: ["electronicSubmission", "electronicAuction", "written", "inPerson"],
        awardStatus: ["pending", "active", "cancelled", "unsuccessful"],
        contractStatus: ["pending", "active", "cancelled", "terminated"]
    }
};

var OCDSObjectComponent = {
    methods: {
        setDefault: function () {
            this[(this.name || this.$options.name)] = Object.assign({}, this.$options.defaultValue);
        },
        removeObject: function () {
            this.$emit("remove", this.index);
        }
    },
    computed: {
        codes: function() {
            return OCDS.codes;
        }
    },
    props: ["value", "name", "index"],
    data: function () {
        var data = { show: false };
        data[(this.name || this.$options.name)] = this.value;
        return data;
    },
    directives: {
        focus: {
            inserted: function (el) {
                el.focus();
            }
        }
    }
};

var OCDSObjectListController = {
    methods: {
        ensureInsertObjectToList: function (container, attribute, entityName) {
            if (!container[attribute])
                this.$set(container, attribute, []);
            this.insertObjectToList(container[attribute], entityName);
        },
        insertObjectToList: function (containerArray, entityName) {
            var newDoc = Vue.util.extend({}, OCDS.defaults[entityName]),
                newIdx = containerArray.length;
            newDoc.id = newIdx+1;
            containerArray.push(newDoc);
        },
        removeObjectFromList: function (containerArray, index) {
            this.$delete(containerArray, index);
        },
        enableList: function (container, name) {
            this.$set(container, name, []);
        }
    }
};

Vue.component("ocds-period", {
    mixins: [OCDSObjectComponent],
    name: "period",
    template: "#vtemplate-ocds-period",
    defaultValue: {
        startDate: null,
        endDate: null
    }
});
Vue.component("ocds-amount", {
    mixins: [OCDSObjectComponent],
    name: "amount",
    template: "#vtemplate-ocds-amount",
    methods: {
        enableField: function () {
            this.amount = { currency: "", amount: 0.0 };
        },
        disableField: function () {
            this.amount = null;
        }
    }
});
Vue.component('ocds-document', {
    mixins: [OCDSObjectComponent],
    name: "document",
    template: "#vtemplate-ocds-document",
});
Vue.component('ocds-organization', {
    mixins: [OCDSObjectComponent],
    name: "party",
    template: "#vtemplate-ocds-organization",
});
Vue.component('ocds-milestone', {
    mixins: [OCDSObjectComponent],
    name: "milestone",
    template: "#vtemplate-ocds-milestone",
});
Vue.component('ocds-item', {
    mixins: [OCDSObjectComponent],
    name: "item",
    template: "#vtemplate-ocds-item",
});
Vue.component('ocds-transaction', {
    mixins: [OCDSObjectComponent],
    name: "transaction",
    template: "#vtemplate-ocds-transaction",
});
Vue.component('ocds-award', {
    mixins: [OCDSObjectComponent, OCDSObjectListController],
    name: "award",
    props: ["parties"],
    template: "#vtemplate-ocds-award",
    methods: {
    }
});
Vue.component('ocds-contract', {
    mixins: [OCDSObjectComponent, OCDSObjectListController],
    name: "contract",
    props: ["parties", "awards"],
    template: "#vtemplate-ocds-contract",
    methods: {
    }
});

function init_single_editor(data, id) {
    if (data == null) {
        data = OCDS.GetEmptyDocument(id);
    }
    var vueInstance = new Vue({
        mixins: [OCDSObjectListController],
        el: '#app',
        data: {
            vmodel: {
                sortBy: "date",
                expandedItems: {}
            },
            model: {
                releases: [data],
            }
        },
        computed: {
            codes: function() {
                return OCDS.codes;
            }
        },
        created: function () {
        },
        methods: {
            newRls: function () {
                var newEntity = Vue.util.extend({}, this.defaults.release),
                    newIdx = this.model.releases.length;
                this.model.releases.push(newEntity);
                this.$set(this.vmodel.expandedItems, 'rls-'+newIdx, true);
            },
            sortRlsByDate: function (releases) {
                var i = 0;
                var idx = releases.map(function () { return i++; })
                return idx.sort(function (a, b) {
                    var datea = new Date(releases[a].releases[0].date), dateb = new Date(releases[b].releases[0].date);
                    return a > b;
                } );
            },
            toggleSection: function (rlsIdx, sectionType) {
                if (this.model.releases[rlsIdx].releases[0][sectionType] === undefined) {
                    var newEntity = {};
                    if (OCDS.defaults[sectionType]) {
                        newEntity = Vue.util.extend({}, OCDS.defaults[sectionType]);
                    }
                    this.$set(this.model.releases[rlsIdx].releases[0], sectionType, newEntity);
                }
                else {
                    this.$delete(this.model.releases[rlsIdx].releases[0], sectionType);
                }
            },
            toggleItem: function (itemPath) {
                if (this.vmodel.expandedItems[itemPath] != undefined)
                    this.vmodel.expandedItems[itemPath] = !this.vmodel.expandedItems[itemPath];
                else
                    this.$set(this.vmodel.expandedItems, itemPath, true);
            },
            addParty: function (rlsIdx) {
                var newParty = Vue.util.extend({}, OCDS.defaults.organization),
                    newIdx = this.model.releases[rlsIdx].releases[0].parties.length;
                this.model.releases[rlsIdx].releases[0].parties.push(newParty);
                this.$set(this.vmodel.expandedItems, 'rls-'+rlsIdx+'/party-'+newIdx, true);
            },
            updateBuyer: function (buyer, party) {
                buyer.name = party.name;
            },
            addDocument: function (rlsIdx, section) {
                var newDoc = Vue.util.extend({}, OCDS.defaults.document),
                    newIdx = this.model.releases[rlsIdx].releases[0][section].documents.length;
                this.model.releases[rlsIdx].releases[0][section].documents.push(newDoc);
                this.$set(this.vmodel.expandedItems, 'rls-'+rlsIdx+'/doc-'+newIdx, true);
            },
            addMilestone: function (rlsIdx, section) {
                var newDoc = Vue.util.extend({}, OCDS.defaults.milestone),
                    newIdx = this.model.releases[rlsIdx].releases[0][section].milestones.length;
                this.model.releases[rlsIdx].releases[0][section].milestones.push(newDoc);
                this.$set(this.vmodel.expandedItems, 'rls-'+rlsIdx+'/milestone-'+newIdx, true);
            },
            addItem: function (rlsIdx, section) {

            },
            addAward: function (rlsIdx) {
                var newAward = Vue.util.extend({}, OCDS.defaults.award),
                    newIdx =  0;
                if (!this.model.releases[rlsIdx].releases[0].awards) {
                    this.$set(this.model.releases[rlsIdx].releases[0], "awards", []);
                }
                else {
                    newIdx = this.model.releases[rlsIdx].releases[0].awards.length;
                }
                this.model.releases[rlsIdx].releases[0].awards.push(newAward);
                this.$set(this.vmodel.expandedItems, 'rls-'+rlsIdx+'/award-'+newIdx, true);
            }
        },
        watch: {
        },
        directives: {
            focus: {
                inserted: function (el) {
                    el.focus()
                }
            }
        }
    });

    return vueInstance;
}
//window.onbeforeunload = function() {
//    return "Desea salir de la aplicación sin guardar los cambios?";
//};
