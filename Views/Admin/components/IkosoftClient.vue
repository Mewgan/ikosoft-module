<style>
    .ikosoft-client #datatable{
        width: 100% !important;
    }
    .ikosoft-client #datatable .btn{
        margin: 0 2px;
    }
    .ikosoft-client h4{
        display: inline-block;
    }
</style>

<template>
    <div class="ikosoft-client">
        <section class="style-default-bright">

            <div class="section-header">
                <ol class="breadcrumb">
                    <li class="active">Sites Web</li>
                </ol>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-md-12">
                        <h4>Liste des sites web</h4>
                        <div class="btn-group pull-right mb10 group-action">
                            <button type="button" data-toggle="dropdown" class="btn ink-reaction btn-default">Sélection</button>
                            <button type="button" class="btn ink-reaction btn-primary dropdown-toggle"
                                    data-toggle="dropdown" aria-expanded="false"><i
                                    class="fa fa-caret-down"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right animation-dock" role="menu">
                                <li><a @click="updateWebsiteState(1)"><i
                                        class="fa fa-fw fa-check text-primary"></i> Activer</a></li>
                                <li><a @click="updateWebsiteState(0)"><i
                                        class="fa fa-fw fa-times text-warning"></i> Désactiver</a></li>
                            </ul>
                        </div>
                        <a :href="exporUrl" target="_blank" class="btn pull-right mr10 ink-reaction btn-raised btn-primary"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Exporter tout les actifs</a>
                    </div><!--end .col -->
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <datatable :config="datatable_config" :api="api" :reload="reload_datatable"
                                   :callback="callback" @updateSelectedItems="updateSelectedItems"></datatable>
                    </div>
                </div><!--end .row -->

            </div><!--end .section-body -->
        </section>

    </div>
</template>


<script type="text/babel">

    import moment from 'moment'

    import {website_api} from '@front/api'
    import {ikosoft_api} from '../api'
    import {mapGetters, mapActions} from 'vuex'

    export default
    {
        components: {
            Datatable: resolve => {
                require(['@front/components/Helper/Datatable.vue'], resolve)
            }
        },
        data () {
            return {
                api: ikosoft_api.all,
                websites: [],
                selected_websites: [],
                reload_datatable: false,
                trial_days: {
                    d: 30
                }
            }
        },
        computed: {
            ...mapGetters(['system']),
            exporUrl(){
                return ikosoft_api.export_users
            },
            datatable_config(){
                return {
                    dom: "BClfrtip",
                    selectionType: 'checkbox',
                    columns: {
                        'Société': {"data": "society"},
                        'Utilisateur': {"data": "full_name"},
                        'E-mail': {"data": "email"},
                        'Site web': {"data": "website"},
                        'Etat': {"data": "state"},
                        'Date de création': {"data": "registered_at"},
                        'Action': {"data": null, "orderable": false, "defaultContent": ""}
                    }
                }
            }
        },
        methods: {
            ...mapActions(['read', 'update']),
            updateWebsiteState (state) {
                if (this.selected_websites.length > 0) {
                    this.update({
                        api: website_api.change_state,
                        value: {
                            state: parseInt(state),
                            ids: this.selected_websites
                        }
                    }).then(() => {
                        this.selected_websites = [];
                        this.reload_datatable = !this.reload_datatable;
                    });
                }
            },
            callback(nRow, aData, iDisplayIndex, iDisplayIndexFull){
                $(nRow).attr("data-id", aData['id']);
                let registered = moment(aData['registered_at'].date);
                let total_days = moment().diff(registered, 'days');
                $('td:eq(6)', nRow).html(registered.format('DD/MM/YYYY à HH:mm:ss') + ' (+' + total_days + 'j)');
                if (aData['website'] == null) {
                    $('td:eq(4)', nRow).html('Pas de site web');
                } else {
                    if (aData['state'] == '1') {
                        if(total_days <= this.trial_days.d)
                            $('td:eq(5)', nRow).html(`<i class="fa fa-clock-o text-warning" aria-hidden="true"> Période d'éssai</i>`);
                        else
                            $('td:eq(5)', nRow).html(`<i class="fa fa-check text-success" aria-hidden="true"> Actif</i>`);
                    } else {
                        $('td:eq(5)', nRow).html(`<i class="fa fa-times text-danger" aria-hidden="true"> Inactif</i>`);
                    }
                    let website = (aData['website'].substring(0, 4) !== 'http')
                            ? this.system.domain + this.system.public_path + '/site/' + aData['website'] : aData['website'];
                    $('td:eq(4)', nRow).html('<a href="' + website + '" target="_blank">' + website + '</a>');
                    $('td:eq(7)', nRow).html('<a title="Voir le site" class="btn btn-default" target="_blank" href="' + website + '"><i class="fa fa-eye" aria-hidden="true"></i></a>');
                }
            },
            updateSelectedItems(items){
                this.selected_websites = items;
            }
        },
        created(){
            this.read({api: ikosoft_api.get_trial_days}).then((response) => {
                if(response.data.resource !== undefined) this.trial_days = response.data.resource
            })
        }
    }
</script>
