<style>
    .ikosoft-module .card-body .tile-text{
        display: inline-block !important;
        width: 50%;
    }
    .ikosoft-module .card-body .switch{
        padding: 5px 0;
    }
</style>

<template>
    <section class="ikosoft-module">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-head style-primary">
                        <header>Activer la mise à jour automatique des données "Ikosoft" :</header>
                        <div class="tools">
                            <div class="switch">
                                <label>
                                    Désactivé
                                    <input @click="updateState" v-model="importer.to_update" type="checkbox">
                                    <span class="lever"></span>
                                    Activé
                                </label>
                            </div>
                        </div>
                    </div><!--end .card-head -->
                </div><!--end .card -->
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-head style-primary">
                        <header>Informations</header>
                    </div><!--end .card-head -->
                    <div class="card-body">
                        <div class="col-md-12"><h4 class="pull-left">Identifiant Ikosoft :</h4><span class="pull-right">{{ importer.uid }}</span></div>
                        <div class="col-md-12"><h4 class="pull-left">Initialisé :</h4><span class="pull-right" v-if="importer.created_at != null">{{ importer.created_at.date | moment('DD MMMM YYYY à HH:mm') }}</span></div>
                        <div class="col-md-12"><h4 class="pull-left">Dernière mise à jour :</h4><span class="pull-right" v-if="importer.updated_at != null">{{ importer.updated_at.date | moment('DD MMMM YYYY à HH:mm') }}</span></div>
                    </div><!--end .card-body -->
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-head style-primary">
                        <header>Choisir les données à importer</header>
                        <div class="tools">
                            <a @click="updateImport()" class="btn btn-default"><i class="fa fa-save"></i> Enregistrer</a>
                        </div>
                    </div><!--end .card-head -->
                    <div class="card-body">
                        <ul class="list">
                            <li v-for="(load, key) in importer.data" class="tile">
                                <div class="tile-content ink-reaction">
                                    <div class="tile-text">{{ loader[key] }}</div>
                                    <div class="switch pull-right">
                                        <label>
                                            Désactivé
                                            <input @click="changeState(load, key)" :checked="isEnable(load)" type="checkbox">
                                            <span class="lever"></span>
                                            Activé
                                        </label>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div><!--end .card-body -->
                </div>
            </div>
        </div>
    </section>
</template>

<script type="text/babel">

    import {ikosoft_api} from '../api'
    import {mapActions} from 'vuex'

    export default{

        data(){
            return {
                website_id: this.$route.params.website_id,
                loader: {
                    'SalonInformation' : 'Informations sur le salon',
                    'TimeTable' : 'Les horaires',
                    'Suppliers' : 'Les partenaires',
                    'Pictures' : 'Les images',
                    'Employees' : 'L\'équipe',
                    'ServicesFamilies' : 'Les familles de services',
                    'Services' : 'Les services'
                },
                importer: {
                    data: [],
                    to_update: false,
                    created_at : null,
                    updated_at : null,
                }
            }
        },
        methods: {
            ...mapActions(['read', 'update']),
            isEnable(load){
                return (load == 1 || load == '1');
            },
            changeState(load, i){
                this.importer.data[i] = (load == 1 || load == '1') ? 0 : 1;
            },
            updateImport(){
                this.update({
                    api: ikosoft_api.update + this.importer.id,
                    value: this.importer
                });
            },
            updateState(){
                let state = (this.importer.to_update) ? 1 : 0;
                this.update({api: ikosoft_api.update_state + this.importer.id + '/' + state});
            }
        },
        created(){
            this.read({api: ikosoft_api.get_by_website + this.website_id }).then((response) => {
                if(response.data.resource !== undefined)
                    this.importer = response.data.resource;
            })
        }
    }
</script>