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
                        <header>Mise à jour automatique de toute les données "Ikosoft" :</header>
                        <div class="tools">
                            <div class="switch">
                                <label>
                                    Désactivé
                                    <input v-model="importer.to_update" type="checkbox">
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
                        <header>Information</header>
                    </div><!--end .card-head -->
                    <div class="card-body">
                        <div class="col-md-12"><h4 class="pull-left">Initialisé :</h4><span class="pull-right" v-if="importer.created_at != null">{{ importer.created_at.date | moment('DD MMMM YYYY à HH:mm') }}</span></div>
                        <div class="col-md-12"><h4 class="pull-left">Dernière mise à jour :</h4><span class="pull-right" v-if="importer.updated_at != null">{{ importer.updated_at.date | moment('DD MMMM YYYY à HH:mm') }}</span></div>
                    </div><!--end .card-body -->
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-head style-primary">
                        <header>Choisir les données a mettre à jour</header>
                    </div><!--end .card-head -->
                    <div class="card-body">
                        <ul class="list">
                            <li class="tile">
                                <div class="tile-content ink-reaction">
                                    <div class="tile-text">Inbox</div>
                                    <div class="switch pull-right">
                                        <label>
                                            Désactivé
                                            <input type="checkbox">
                                            <span class="lever"></span>
                                            Activé
                                        </label>
                                    </div>
                                </div>
                            </li>
                            <li class="tile">
                                <div class="tile-content ink-reaction">
                                    <div class="tile-text">Starred</div>
                                    <div class="switch pull-right">
                                        <label>
                                            Désactivé
                                            <input type="checkbox">
                                            <span class="lever"></span>
                                            Activé
                                        </label>
                                    </div>
                                </div>
                            </li>
                            <li class="tile">
                                <div class="tile-content ink-reaction">
                                    <div class="tile-text">Sent email</div>
                                    <div class="switch pull-right">
                                        <label>
                                            Désactivé
                                            <input type="checkbox">
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
    import {mapGetters, mapActions} from 'vuex'

    export default{

        data(){
            return {
                website_id: this.$route.params.website_id,
                importer: {
                    to_update: false,
                    created_at : null,
                    updated_at : null,
                }
            }
        },
        methods: {
            ...mapActions(['read', 'update']),
        },
        created(){
            this.read({api: ikosoft_api.get_by_website + this.website_id }).then((response) => {
                if(response.data.resource !== undefined)
                    this.importer = response.data.resource;
            })
        },
        mounted(){

        }
    }
</script>