<template>
    <section class="ikosoft-dashboard">
        <div class="section-body">
            <div class="row">

                <!-- BEGIN ALERT - REVENUE -->
                <div class="col-md-3 col-sm-6">
                    <div class="card">
                        <div class="card-body no-padding">
                            <div class="alert alert-callout alert-info no-margin">
                                <h1 class="pull-right text-info"><i class="md md-account-child"></i></h1>
                                <strong class="text-xl">{{summary.users}}</strong><br/>
                                <span class="opacity-50">Utilisateurs</span>
                            </div>
                        </div>
                        <!--end .card-body -->
                    </div>
                    <!--end .card -->
                </div>
                <!--end .col -->
                <!-- END ALERT - REVENUE -->

                <!-- BEGIN ALERT - VISITS -->
                <div class="col-md-3 col-sm-6">
                    <div class="card">
                        <div class="card-body no-padding">
                            <div class="alert alert-callout alert-warning no-margin">
                                <h1 class="pull-right text-warning"><i class="md md-web"></i></h1>
                                <strong class="text-xl">{{summary.websites}}</strong><br/>
                                <span class="opacity-50">Sites actifs</span>
                            </div>
                        </div>
                        <!--end .card-body -->
                    </div>
                    <!--end .card -->
                </div>
                <!--end .col -->
                <!-- END ALERT - VISITS -->

                <!-- BEGIN ALERT - BOUNCE RATES -->
                <div class="col-md-3 col-sm-6">
                    <div class="card">
                        <div class="card-body no-padding">
                            <div class="alert alert-callout alert-danger no-margin">
                                <h1 class="pull-right text-danger"><i class="md md-play-shopping-bag"></i></h1>
                                <strong class="text-xl">{{summary.modules}}</strong><br/>
                                <span class="opacity-50">Modules</span>
                            </div>
                        </div>
                        <!--end .card-body -->
                    </div>
                    <!--end .card -->
                </div>
                <!--end .col -->
                <!-- END ALERT - BOUNCE RATES -->

                <!-- BEGIN ALERT - TIME ON SITE -->
                <div class="col-md-3 col-sm-6">
                    <div class="card">
                        <div class="card-body no-padding">
                            <div class="alert alert-callout alert-success no-margin">
                                <h1 class="pull-right text-success"><i class="md md-computer"></i></h1>
                                <strong class="text-xl">{{summary.themes}}</strong><br/>
                                <span class="opacity-50">Thèmes actifs</span>
                            </div>
                        </div>
                        <!--end .card-body -->
                    </div>
                    <!--end .card -->
                </div>
                <!--end .col -->
                <!-- END ALERT - TIME ON SITE -->
            </div>

            <!--end .row -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-head style-primary">
                            <header>Statistiques</header>
                            <div class="tools">
                                <div class="btn-group">
                                    <button @click="loadStats" class="btn btn-icon-toggle btn-refresh"><i
                                            class="md md-refresh"></i></button>
                                </div>

                            </div>
                        </div>
                        <div class="card-body small-padding text-center">
                            <div class="row">
                                <div class="col-md-5">
                                    <datepicker label="Début" id="start_datepicker" :options="date_options"
                                                @updateDatepicker="updateStartDate"></datepicker>
                                </div>
                                <div class="col-md-5">
                                    <datepicker label="Fin" id="end_datepicker" :options="date_options"
                                                @updateDatepicker="updateEndDate"></datepicker>
                                </div>
                                <div class="col-md-2">
                                    <button @click="loadStats" class="btn btn-primary stats-btn">Go</button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="registration-charts"></canvas>
                        </div><!--end .card-body -->
                    </div><!--end .card -->
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-head style-primary">
                            <header>Derniers sites</header>
                            <div class="tools">
                                <div class="btn-group">
                                    <div class="btn-group">
                                        <a href="#" class="btn btn-icon-toggle dropdown-toggle"
                                           data-toggle="dropdown" aria-expanded="false"><i
                                                class="fa fa-angle-down"></i></a>
                                        <ul class="dropdown-menu animation-dock pull-right menu-card-styling"
                                            role="menu" style="text-align: left;">
                                            <li v-for="nbr in list_websites"><a @click="loadLastWebsites(nbr)">Lister
                                                : {{nbr}}</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div><!--end .card-head -->
                        <div class="card-body no-padding scroll">
                            <ul class="list divider-full-bleed">
                                <li class="tile" v-for="iko in imports">
                                    <div class="tile-content">
                                        <div class="tile-text">
                                            {{iko.website.society.name}}<br>
                                            <small>{{iko.website.created_at.date | moment('DD-MM-YYYY à HH:mm')}}
                                            </small>
                                        </div>
                                    </div>
                                    <a class="btn btn-flat ink-reaction" target="_blank"
                                       :href="websiteUrl(iko.website.domain)">
                                        <i class="md md-remove-red-eye text-default-light"></i>
                                    </a>
                                </li>
                            </ul>
                        </div><!--end .card-body -->
                    </div><!--end .card -->
                </div><!--end .col -->
                <!-- END NEW REGISTRATIONS -->
            </div>

        </div>
    </section>
</template>

<script type="text/babel">


    import Chart from 'chart.js'
    import moment from 'moment'

    import {ikosoft_api} from '../api'
    import {mapGetters, mapActions} from 'vuex'

    export default
    {
        components: {
            Datepicker: resolve => { require(['@front/components/Helper/Datepicker.vue'], resolve) }
        },
        data () {
            return {
                date_options: {
                    format: 'mm-yyyy',
                    minViewMode: 1,
                    endDate: '+1m'
                },
                summary: {
                    users: 0,
                    websites: 0,
                    themes: 0,
                    modules: 0
                },
                list_websites: [5, 10, 15],
                stats: {
                    data: [],
                    labels: []
                },
                start_date: null,
                end_date: null,
                imports: []
            }
        },
        computed: {
            ...mapGetters(['system'])
        },
        methods: {
            ...mapActions(['read']),
            updateStartDate(val){
                this.start_date = '01-' + val;
            },
            updateEndDate(val){
                this.end_date = '01-' + val;
            },
            websiteUrl(domain){
                return (domain.substring(0, 4) !== 'http')
                        ? this.system.domain + this.system.public_path + '/site/' + domain
                        : domain;
            },
            loadPanelSummary(){
                this.read({api: ikosoft_api.get_panel_summary}).then((response) => {
                    this.summary = response.data;
                });
            },
            loadLastWebsites(nbr = 5){
                this.read({api: ikosoft_api.get_last + nbr}).then((response) => {
                    this.imports = response.data;
                });
            },
            loadStats(){
                let start = (this.start_date == null) ? moment().subtract(4, 'months').format('01-MM-YYYY') : this.start_date;
                let end = (this.end_date == null) ? moment().add(1, 'months').format('01-MM-YYYY') : this.end_date;
                this.start_date = this.end_date = null;
                this.read({
                    api: ikosoft_api.list_between_dates, options: {
                        params: {start, end}
                    }
                }).then((response) => {
                    if (response.data.dates !== undefined) {
                        new Chart($("#registration-charts"), {
                            type: 'line',
                            data: {
                                labels: response.data.labels,
                                datasets: [
                                    {
                                        label: "Inscrits",
                                        data: response.data.dates
                                    }
                                ],
                                options: {
                                    scales: {
                                        xAxes: [{
                                            type: 'linear',
                                            position: 'bottom'
                                        }]
                                    }
                                }
                            }
                        });
                    }
                })
            }
        },
        created(){
            this.loadPanelSummary();
            this.loadLastWebsites();
        },
        mounted(){
            this.loadStats();
        }
    }
</script>