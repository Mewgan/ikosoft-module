export const provider = 'ikosoft';

export var routes = [
    {
        path: 'ikosoft',
        name: 'module:ikosoft',
        component: resolve => {
            require(['./components/Ikosoft.vue'], resolve)
        }
    }
];

export var global_routes = [
    {
        title: 'Tableau de bord',
        path: '/ikosoft/dashboard',
        name: 'module:ikosoft:dashboard',
        component: resolve => {
            require(['./components/IkosoftDashboard.vue'], resolve)
        }
    },
    {
        title: 'Clients',
        path: '/ikosoft/client',
        name: 'module:ikosoft:client',
        component: resolve => {
            require(['./components/IkosoftDashboard.vue'], resolve)
        }
    }
];

export var content_routes = {};

export default {
    provider, routes, global_routes, content_routes
}