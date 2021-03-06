/**
* Lite Publisher
* Copyright (C) 2010 - 2015 Vladimir Yushko http://litepublisher.ru/ http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

(function ($) {
  'use strict';
  
var jsonrpcSettings  = $.jsonrpcSettings = {
guid: $.now(),
url: ltoptions.ajaxurl + "/admin/jsonserver.php",
onargs: function(args) {
      var user = litepubl.getuser();
      if (user.id) {
var params = args.params;
        params.litepubl_user_id = user.id;
        params.litepubl_user = user.pass;
        params.litepubl_user_regservice = user.regservice;
      }
}
};

$.jsonrpc = function(args) {
      args = $.extend({
url: jsonrpcSettings.url,
        type: 'post',
        method: '',
      params: {},
        slave: false,
        callback: false,
        error: false,
        cache: false
      }, args);

jsonrpcSettings.onargs(args);
      var params = args.params;
      if (args.slave) {
        params.slave = {
          method: args.slave.method,
          params: args.slave.params
        };
      }
      
      var ajax = {
        type: args.type,
        url: args.url,
        cache: args.cache,
        dataType: "json",
        success: function(r) {
          if (typeof r === "object") {
            if ("result" in r) {
              if ($.isFunction(args.callback)) args.callback(r.result);
              if (args.slave && $.hasprop(r.result, 'slave')) {
                var slave = args.slave;
                var slaveresult = r.result.slave;
                if ($.hasprop(slaveresult, 'error')) {
                  if ($.hasprop(slave, 'error') && $.isFunction(slave.error)) slave.error(slaveresult.error.message, slaveresult.error.code);
                } else {
                  if ($.hasprop(slave, 'callback') && $.isFunction(slave.callback)) slave.callback(slaveresult);
                }
              }
            } else if ("error" in r) {
              if ($.isFunction(args.error)) args.error(r.error.message, r.error.code);
            }
          }
        }
      };
      
      if (args.type == 'post') {
        if (!args.cache) ajax.url = ajax.url + '?_=' + jsonrpcSettings.guid++;
        ajax.data = $.toJSON({
          jsonrpc: "2.0",
          method: args.method,
          params: params,
          id: jsonrpcSettings.guid++
        });
      } else {
        ajax.type = 'get';
        params.method = args.method;
        ajax.data = params;
      }
      
      return $.ajax(ajax).fail( function(jq, textStatus, errorThrown) {
        if ($.isFunction(args.error)) args.error(jq.responseText, jq.status);
      });
    };

    $.hasprop= function(obj, prop) {
      return (typeof obj === "object") && (prop in obj);
    };
    

}(jQuery));