<?php

class DynamicRouter {

    protected static

        /**
         * Flag that determines if we are on a virtual page or not
         * @var bool
         */
        $bsIsVirtualPage = false,

        /**
         * Array of custom routes
         * @var array
         */
        $aRoutes = array (),

        /**
         * Array of custom created query vars
         * @var array
         */
        $aQueryVars = array (),

        /**
         * Stores the currently used route
         * @var array|false
         */
        $aCurrentRoute = false;



    /**
     * Create new route to process our pages
     *
     * @param string $route Regular expression for the routing ^route$
     * for example will create /route/ page
     * @param string|null $template The template name to use, null to automate
     * @param string|null $title The page title appearing in wp_title()
     * @param array $arguments Custom arguments to be parsed
     * @throws Exception Throws exception when given invalid data
     * @return void
     */
    public static function create($route, $template, $title = null, $arguments = array ()) {

        // validate our route string
        if (!is_string($route)) {
            throw new Exception('Argument #1 of DynamicRouter::create() must be a regular expression string');
        }

        // validate our template string
        if (!is_string($template)) {
            throw new Exception('Argument #2 of DynamicRouter::create() must be a string referencing a WordPress template file');
        }

        // validate our route arguments
        if (!is_array($arguments)) {
            throw new Exception('Argument #3 of DynamicRouter::create() must be an empty array or array of possible regex matches');
        }

        // add to our array of routes
        self::$aRoutes[$route] = array(
            'template'  => $template,
            'title'     => $title,
            'arguments' => $arguments,
        );
    }


    /**
     * Static method to execute our rewrites
     * @return void
     */
    public static function handle () {

        // add rewrite action
        add_action('init',              array (__CLASS__, 'actionRewrite'));

        // register our custom filters
        add_action('query_vars',        array (__CLASS__, 'actionQueryVars'));

        // determine how to handle our templates
        add_action('wp',  array (__CLASS__, 'actionTemplateInclude'));
    }

    public static function getPageName()
    {
        return self::$bsIsVirtualPage;
    }


    /**
     * Check to see if we are on a virtual page or not
     *
     * @param null|string $slug The string to compare against. Null
     * to simply check if it is a page or not
     * @return bool
     */
    public static function isPage($slug = null) {

        if(is_null($slug)) {
            return (bool)self::$bsIsVirtualPage;
        } else {
            return (bool)(self::$bsIsVirtualPage === $slug);
        }

    }


    /**
     * Retrieves query variable from URL
     * (extension of WordPress get_query_var() method)
     *
     * @param string $field Query var to obtain
     * @param mixed $default The default value to return
     * @throws Exception Exception when invalid data is passed
     * @return mixed
     */
    public static function getQueryVar ($field, $default = false) {

        if(is_string($field)) {

            // return our query var
            if($return = get_query_var(sprintf('DR%s', $field))) {
                return $return;
            }

            // see if we have a default argument
            if($default === FALSE && array_key_exists($field, self::$aCurrentRoute['arguments'])) {
                // set our new default
                $default = self::$aCurrentRoute['arguments'][$field];
            }

            // return our default
            return $default;


        } else {
            // throw exception when we haven't got a string
            throw new Exception('DynamicRouter::getQueryVar() requires the first argument to be a string');
        }
    }


    /**
     * Get query var - alias of DynamicRouter::getQueryVar()
     *
     * @param string $field Query var to obtain
     * @param mixed $default The default value to return
     * @return mixed
     */
    public static function get_query_var($field, $default = false) {
        return self::getQueryVar($field);
    }


    /**
     * Gets the current DynamicRouter route being accessed
     *
     * @param string|null $matched_rule rule that was matched (regex)
     * @return bool|string Gets the current route or false if route is invalid
     */
    public static function getCurrentRoute (&$matched_rule = null) {

        // get our wp global
        global $wp;

        // check if we are running a router rule
        if(array_key_exists($wp->matched_rule, self::$aRoutes)) {

            $matched_rule = $wp->matched_rule;

            // set our current route
            self::$aCurrentRoute = self::$aRoutes[$wp->matched_rule];
        }

        // return our current route
        return self::$aCurrentRoute;
    }


    /**
     * WP Action to create our rewrite rules
     * @uses init action
     * @return void
     */
    public static function actionRewrite () {

        // create a rewrite endpoint
        add_rewrite_endpoint('router', EP_PERMALINK);

        // add rewrite tag
        add_rewrite_tag('%DynamicRouter%', '([^&]+)');

        // loop through our rewrites
        foreach(self::$aRoutes as $sRegex => $aRoute) {


            $sRouteKey = md5(serialize(self::$aRoutes[$sRegex]));
            self::$aRoutes['key'] = $sRouteKey;

            // set our $sArgs to be null
            $sArgs = '?DynamicRouter=' . $sRegex;

            // check if we have any arguments
            if(is_array($aRoute['arguments']) && count($aRoute['arguments'])) {

                // set our match number
                $i = 0;

                // loop through our arguments
                foreach($aRoute['arguments'] as $sArgument) {
                    // add argument to our rewrite string
                    self::$aQueryVars[] = sprintf('DR%s', esc_attr($sArgument));
                    $sArgs.= sprintf('&DR%s=$matches[%d]', esc_attr($sArgument), ++$i);
                }
            }

            // create our rewrite rule for this route
            add_rewrite_rule($sRegex, 'index.php' . $sArgs, 'top');
        }
    }


    /**
     * WP Action to register our custom WordPress query vars
     *
     * @uses query_vars action
     * @param array $vars Array of existing query vars
     * @return array Array of updated query vars
     */
    public static function actionQueryVars ($vars) {

        // get our route
        $aRoute = self::getCurrentRoute();

        // add our DynamicRouter variable
        $vars[] = 'DynamicRouter';

        // check we have a valid route with args
        if($aRoute && count($aRoute['arguments'])) {

            // loop through our arguments
            foreach($aRoute['arguments'] as $sVar) {
                $vars[] = 'DR' . $sVar;
            }
        }

        // return our updated vars
        return $vars;
    }


    /**
     * WP Action to determine the template to render
     *
     * @uses template_include action
     * @param string $template The default template to render
     * @return string Returns template to render
     */
    public static function actionTemplateInclude ($wp) {

        // get $wp global
        global $wp_query;

        // get our current route
        $aRoute = self::getCurrentRoute();

        // check if we are running a router rule
        if($aRoute) {

            // modify $wp_query to set page
            $wp_query->is_home = false;
            $wp_query->is_page = false;

            // add our wp_title filters
            add_filter('wp_title', function () {

                // get our current route
                $aRoute = self::getCurrentRoute();

                // return our title if we have one
                if(!is_null($aRoute['title'])) {
                    return $aRoute['title'];
                }
            });

            // add our body class filters
            add_filter('body_class', function ($classes) {

                // get globals
                global $wp;

                // get our current route
                $aRoute = self::getCurrentRoute();

                // set our title if we can
                if(!is_null($aRoute['title'])) {
                    $classes[] = 'router-' . sanitize_title($aRoute['title']);
                }

                // set our matched rule class
                $classes[] = 'router-' . sanitize_title($wp->matched_rule);

                // set our generic router page class
                $classes[] = 'router-page';

                // return our classes
                return $classes;
            });

            // set virtual page flag
            self::$bsIsVirtualPage = sanitize_title($wp->matched_rule);

            if (!is_404()) {

                add_filter('template_include', function ($template) use ($aRoute) {

                    // create our templates
                    {
                        if (is_array($aRoute['template'])) {
                            $aTemplates = $aRoute['template'];
                        } else {
                            $aTemplates = array($aRoute['template']);
                        }

                        // add standard router template
                        $aTemplates[] = 'page-router.php';

                        // add our predefined template
                        $aTemplates[] = $template;
                    }

                    // locate our template and return it
                    return locate_template($aTemplates);// return our default template
                }, 1000);
            }
        }
    }
}
