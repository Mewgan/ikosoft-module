export var global = {
    provider: 'ikosoft',
    icon: 'fa fa-scissors',
    routes: [
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
                require(['./components/IkosoftClient.vue'], resolve)
            }
        }
    ]
};

export var routes = [
    {
        path: 'ikosoft',
        name: 'module:ikosoft',
        component: resolve => {
            require(['./components/Ikosoft.vue'], resolve)
        }
    }
];

export var content_routes = {};

export default {
    global, routes, content_routes
}