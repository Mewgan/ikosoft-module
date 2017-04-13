<style>

</style>

<template>
    <section class="ikosoft-dashboard">

    </section>
</template>

<script type="text/babel">

    import {ikosoft_api} from '../api'
    import {mapActions} from 'vuex'

    export default{

        data(){
            return {

            }
        },
        methods: {
            ...mapActions(['read', 'update']),
        },
        created(){

        },
        mounted(){

        }
    }
</script>