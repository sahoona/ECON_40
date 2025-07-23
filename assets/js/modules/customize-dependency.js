/**
 * WordPress Customizer-specific JavaScript for handling control dependencies.
 *
 * This script is designed to work with the WordPress Customizer's JS API.
 * It dynamically shows or hides Customizer controls based on the values of other controls.
 *
 * This functionality relies on a `customize_dependency` parameter being passed to the control in PHP.
 * The `customize_dependency` parameter should be an array with two keys:
 * - 'controls': An array of control IDs that the current control depends on.
 * - 'value': An array of corresponding values that the dependent controls must have for the current control to be visible.
 *
 * Example usage in PHP:
 * $wp_customize->add_control('my_dependent_control', [
 *     'label' => 'My Dependent Control',
 *     'section' => 'my_section',
 *     'settings' => 'my_dependent_setting',
 *     'type' => 'text',
 *     'customize_dependency' => [
 *         'controls' => ['my_master_control_id'],
 *         'value' => [true], // Can be any value: string, number, boolean
 *     ],
 * ]);
 *
 * @package    GP_Child_Theme
 * @since      1.0.0
 */

( function( api ) {
    if ( ! api ) {
        return;
    }

    /**
     * Toggles the visibility of a control section.
     *
     * @param {object} control - The Customizer control object.
     * @param {boolean} show - Whether to show or hide the control.
     */
    const toggleControl = ( control, show ) => {
        if ( control && control.container ) {
            control.container.toggle( show );
        }
    };

    /**
     * Checks if the dependencies for a given control are met.
     *
     * @param {object} dependentControl - The control whose dependencies are being checked.
     * @returns {boolean} - True if all dependencies are met, false otherwise.
     */
    const checkDependencies = ( dependentControl ) => {
        const { customize_dependency } = dependentControl.params;
        if ( ! customize_dependency || ! customize_dependency.controls || ! customize_dependency.value ) {
            return true; // No dependencies, so it should be visible.
        }

        const { controls, value } = customize_dependency;
        let allDependenciesMet = true;

        controls.forEach( ( masterControlId, index ) => {
            const masterControl = api.control( masterControlId );
            if ( masterControl ) {
                const masterValue = masterControl.setting.get();
                const requiredValue = value[index];
                if ( masterValue !== requiredValue ) {
                    allDependenciesMet = false;
                }

                // Check for transport type and refresh if necessary
                if ( masterControl.setting.transport === 'refresh' ) {
                    wp.customize.previewer.refresh();
                }
            } else {
                allDependenciesMet = false;
            }
        } );

        return allDependenciesMet;
    };

    /**
     * Sets up the initial state and listeners for all controls that have dependencies.
     */
    api.bind( 'ready', () => {
        api.control.each( ( control ) => {
            const { customize_dependency } = control.params;
            if ( ! customize_dependency ) {
                return;
            }

            // Set initial visibility based on current dependency values.
            const isVisible = checkDependencies( control );
            toggleControl( control, isVisible );

            // Listen for changes in the master controls.
            customize_dependency.controls.forEach( ( masterControlId ) => {
                api.control( masterControlId, ( masterControl ) => {
                    masterControl.setting.bind( () => {
                        const shouldShow = checkDependencies( control );
                        toggleControl( control, shouldShow );
                    } );
                } );
            } );
        } );
    } );
} )( window.wp && window.wp.customize );
