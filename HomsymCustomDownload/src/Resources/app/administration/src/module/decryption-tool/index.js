Shopware.Component.register('decryption-test', () => import('../page/decryption-test'));

import deDE from '../../snippet/de-DE.json';
import enGB from '../../snippet/en-GB.json';

Shopware.Module.register('decryption-tool', {
    type: 'plugin',
    name: 'decryptionTool',
    title: 'register.administration.menuItemDownload',
    description: 'register.administration.description',
    color: '#ff3d58',
    icon: 'default-shopping-paper-bag-product',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        test: {
            component: 'decryption-test',
            path: 'decryption/test'
        }
    },

    navigation: [{
        label: 'register.administration.navItemDownload',
        color: '#ffffff',
        path: 'decryption.tool.test',
        icon: 'default-shopping-paper-bag-product',
        parent: 'sw-customer',
        position: 100
    }]

});
