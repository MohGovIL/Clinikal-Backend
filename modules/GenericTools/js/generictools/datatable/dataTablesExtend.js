

/**
 *reload table with new data according the filters
 *@param datatableInstance - instance of datatable
 *@param data - object with parameters
 *@param url - optional - default is the current url with parameter 'filters' in the query string.
 **/
function loadFilteredData(datatableInstance, data, url) {

    var queryString = Object.keys(data).map(function (key) {
        return key + '=' + data[key]
    }).join('&');

    url = (typeof url === 'undefined') ? document.URL : url;

    var rez = datatableInstance.api().ajax.url(url + '?filters&' + queryString).load();
}
