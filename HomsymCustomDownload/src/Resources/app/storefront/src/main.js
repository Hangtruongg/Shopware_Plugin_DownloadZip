import HomsymCustomDownload from './homsym-custom-download/homsym-custom-download.plugin';

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('HomsymCustomDownload', HomsymCustomDownload, '[downloads-zip-plugin]');