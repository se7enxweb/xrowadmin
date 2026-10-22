(function() {
    var methods = {
        showNodeName: function(options)
        {
            var aim = options.type;
            jQuery.ez('xrowadmin::getName', { 'NodeID' : jQuery('#'+aim+'_id').val() }, function(result) {
                jQuery('#' + aim + '_name').html( result.content.template );
            });
        }
    };

    jQuery.fn.xrowadmin = function(method) {
        // Method calling logic
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            jQuery.error('Method ' + method
                    + ' does not exist on jQuery.xrowadmin');
        }

    };

})(jQuery);

