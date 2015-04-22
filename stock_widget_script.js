function stock_widget_datatables_init(widget_root, widget_config) {
    var options = {searching:false,info:false};
    switch(parseInt(widget_config['layout'])) {
        case 1: // Expand - Widget has no height cap, it fills as much space as it needs for the stocks
            options.paging = false;
        break;
        case 2: // Static - Widget has a fixed height, it will truncate extra stocks
            options.paging = true;
            options.pagingType = 'simple';
            options.pageLength = parseInt(widget_config['display_number']);
            options.lengthChange = false;
        break;
        case 3: // Paged - Widget has a fixed height, it will generate paging buttons to handle extra stocks
            options.paging = true;
            options.pagingType = 'simple_numbers';
            options.pageLength = parseInt(widget_config['display_number']);
            options.info = true;
            options.lengthChange = false;
        break;
        case 4: // Scroll - Widget has a fixed height, it will generate a scrollbar to handle extra stocks
            options.paging = false;
            options.scrollY = widget_config['height'];
            options.scrollCollapse = true;
        break;
    }
    options.order = [[parseInt(widget_config['default_sort'])-1,'asc']]; // default sort
    /* 
    options.columns = [];
    for (i = 0; i < widget_config['data_display'].length; i++) {
        // colnum = colnum + parseInt(widget_config['data_display'][i]);
        if (widget_config['data_display'][i] == 1) options.columns.push({'orderable':false});
    } */
    // options.ordering = parseInt(widget_config['sorting_enabled']);       // sorting enabled
    widget_root.dataTable(options);
};
