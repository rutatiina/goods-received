
const Index = () => import('./components/l-limitless-bs4/Index');
const Form = () => import('./components/l-limitless-bs4/Form');
const Show = () => import('./components/l-limitless-bs4/Show');
const SideBarLeft = () => import('./components/l-limitless-bs4/SideBarLeft');
const SideBarRight = () => import('./components/l-limitless-bs4/SideBarRight');

const routes = [

    {
        path: '/goods-received',
        components: {
            default: Index,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Goods Received Notes',
            metaTags: [
                {
                    name: 'description',
                    content: 'Goods Received Notes'
                },
                {
                    property: 'og:description',
                    content: 'Goods Received Notes'
                }
            ]
        }
    },
    {
        path: '/goods-received/create',
        components: {
            default: Form,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Goods Received Note :: Create',
            metaTags: [
                {
                    name: 'description',
                    content: 'Create Goods Received Note'
                },
                {
                    property: 'og:description',
                    content: 'Create Goods Received Note'
                }
            ]
        }
    },
    {
        path: '/goods-received/:id',
        components: {
            default: Show,
            'sidebar-left': SideBarLeft,
            'sidebar-right': SideBarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Goods Received Note',
            metaTags: [
                {
                    name: 'description',
                    content: 'Goods Received Note'
                },
                {
                    property: 'og:description',
                    content: 'Goods Received Note'
                }
            ]
        }
    },
    {
        path: '/goods-received/:id/copy',
        components: {
            default: Form,
        },
        meta: {
            title: 'Accounting :: Sales :: Goods Received Note :: Copy',
            metaTags: [
                {
                    name: 'description',
                    content: 'Copy Goods Received Note'
                },
                {
                    property: 'og:description',
                    content: 'Copy Goods Received Note'
                }
            ]
        }
    },
    {
        path: '/goods-received/:id/edit',
        components: {
            default: Form,
        },
        meta: {
            title: 'Accounting :: Sales :: Goods Received Note :: Edit',
            metaTags: [
                {
                    name: 'description',
                    content: 'Edit Goods Received Note'
                },
                {
                    property: 'og:description',
                    content: 'Edit Goods Received Note'
                }
            ]
        }
    }

]

export default routes
