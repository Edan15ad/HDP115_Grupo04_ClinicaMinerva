import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';

import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Toolbar from 'primevue/toolbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Toast from 'primevue/toast';
import ToastService from 'primevue/toastservice';

import 'primeicons/primeicons.css';

const appName = import.meta.env.VITE_APP_NAME || 'Clínica Minerva';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),

        layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;

            case name.startsWith('auth/'):
                return null;

            case name === 'Dashboard':
                return null;

            case name.startsWith('Paciente/'):
                return null;

            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];

            default:
                return AppLayout;
        }
    },

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: '.dark',
                    },
                },
                ripple: true,
            })
            .use(ToastService)

            // PrimeVue global components
            .component('InputText', InputText)
            .component('Password', Password)
            .component('Checkbox', Checkbox)
            .component('Button', Button)
            .component('Card', Card)
            .component('DataTable', DataTable)
            .component('Column', Column)
            .component('Dialog', Dialog)
            .component('Toolbar', Toolbar)
            .component('IconField', IconField)
            .component('InputIcon', InputIcon)
            .component('Toast', Toast)

            .mount(el);
    },

    progress: {
        color: '#06b6d4',
    },
});

initializeTheme();

initializeFlashToast();