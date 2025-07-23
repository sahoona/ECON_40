( function( api ) {
    api.bind( 'ready', function() {
        // Force all widget areas to be visible
        api.section.each( function( section ) {
            if ( section.id.startsWith( 'sidebar-widgets-' ) ) {
                section.activate();
            }
        } );

        // Force refresh when a menu is assigned to a location.
        api.control.each( function( control ) {
            if ( control.id.startsWith( 'nav_menu_locations[' ) ) {
                control.setting.bind( function() {
                    wp.customize.previewer.refresh();
                } );
            }
        } );
    } );
} )( window.wp.customize );
