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
    routes, content_routes
}