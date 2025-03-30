import './decryption-test.scss';
import template from './decryption-test.html.twig';

export default {
    template,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    data() {
        return {
            encryptedValue: '',
            decryptedOrderID: '',
            decryptedCustomerNumber: '',
        };
    },

    methods: {
        decryptComment() {
            if (!this.encryptedValue) {
                this.decryptedOrderID = 'No value provided';
                this.decryptedCustomerNumber = '';
                return;
            }

            const result = this.decryptCommentLogic(this.encryptedValue);
            if (result === 'Invalid encrypted value') {
                this.decryptedOrderID = 'Invalid value';
                this.decryptedCustomerNumber = '';
            } else {
                this.decryptedOrderID = result.orderID;
                this.decryptedCustomerNumber = result.customerNumber;
            }
        },

        decryptCommentLogic(encryptedValue) {
            if (!encryptedValue || encryptedValue.length !== 28) {
                return 'Invalid encrypted value';
            }

            let customerNumber = encryptedValue[24] +
                encryptedValue[20] +
                encryptedValue[17] +
                encryptedValue[5] +
                encryptedValue[1];

            let orderID = encryptedValue[11] +
                encryptedValue[3] +
                encryptedValue[25] +
                encryptedValue[15] +
                encryptedValue[22];

            return { orderID, customerNumber };
        }
    }
};
