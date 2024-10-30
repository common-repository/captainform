<?php
defined('ABSPATH') or die('No direct access!');

/**
 * The class for the captainform plugin form submissions
 *
 * This is used to handle the form submissions for the captainform plugin
 *
 * @since      2.0.0
 * @package    Captainform
 * @subpackage Captainform/includes/integrations
 * @author     captainform <team@captainform.com>
 */
class Captainform_WP_Submissions extends Captainform_Integrations_Handler
{
    /**
     * @since   2.0.0
     */
    const OPTION_DB_NAME = 'captainform_wp_submissions_public_key';

    /**
     * @since   2.0.0
     * @param null $param
     */
    public static function connect($param = null)
    {
        parent::connect(self::OPTION_DB_NAME);
    }

    /**
     * @since   2.0.0
     * @param null $param
     */
    public static function check_connection($param = null)
    {
        parent::check_connection(self::OPTION_DB_NAME);
    }

    /**
     * @since   2.0.0
     * @return array
     */
    public static function get_integration_hooks()
    {
        return array(
            'captainform_submissions_connect' => 'connect',
            'captainform_submissions_query' => 'query',
        );
    }

    /**
     * @since   2.0.0
     * @access  private
     * @param   $from
     * @param   $to
     * @param   $subject
     * @return  mixed
     */
    private static function str_replace_first($from, $to, $subject)
    {
        $from = '/'.preg_quote($from, '/').'/';
        return preg_replace($from, $to, $subject, 1);
    }

    /**
     * @since   2.0.0
     */
    public static function createTable()
    {
        global $wpdb;
        $tablename = $wpdb->prefix . 'captainform_submissions';
        $sql = "CREATE TABLE IF NOT EXISTS `$tablename` (
		`x_id` bigint(20) NOT NULL auto_increment,
		`x_fid` bigint(20) NOT NULL,
		`x_uid` bigint(20) NOT NULL,
		`x_mid` char(24) NOT NULL,
		`x_date` datetime NOT NULL,
		`x_ip` varchar(15) NOT NULL,
		`x_cc` char(2) NOT NULL,
		`x_refid` varchar(50) NOT NULL,
		`x_xml` mediumblob NOT NULL,
		`x_del` TINYINT NOT NULL DEFAULT '0',
		`x_status` TINYINT NOT NULL DEFAULT '1',
		`x_paymdone` TINYINT NOT NULL DEFAULT '0',
		`x_entryid` MEDIUMINT(9) NOT NULL DEFAULT '0',
		`x_approved` TINYINT NOT NULL DEFAULT '0',
		PRIMARY KEY  (`x_id`),
		UNIQUE KEY `x_mid` (`x_mid`),
		KEY `x_fid` (`x_fid`, `x_date`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
        $wpdb->query($sql);
    }

    /**
     * @since   2.0.0
     */
    public static function query()
    {

        if (!self::authenticate(self::OPTION_DB_NAME)) {
            echo self::message("There was an error while trying to authenticate with WordPress", 0);
            exit();
        }

        global $wpdb;
        self::createTable();
        // secured encrypted query executed on both WP and 123 databases
        // phpcs:disable WordPressDotOrg.sniffs.DirectDB.UnescapedDBParameter
        $statement = $_REQUEST['statement'];
        $select_count = $_REQUEST['select_count'];
        $query = Captainform_Encrypt::decrypt($_REQUEST['query']);
        $query = self::str_replace_first('[wp-prefix]', $wpdb->prefix, $query);
        if($statement == 'SELECT')
        {
            if($select_count) $return = $wpdb->get_var($query);
            else $return = $wpdb->get_results($query, ARRAY_A); //$return = json_encode($return);
        }
        elseif($statement == 'INSERT' || $statement == 'UPDATE' || $statement == 'DELETE')
        {
            $return = $wpdb->query($query);
        }
        elseif($statement == 'TABLE_PREFIX')
        {
        	$return = $wpdb->prefix;
        }

        $return = json_encode(array('captainform_valid_response',$return));
        // phpcs:enable WordPressDotOrg.sniffs.DirectDB.UnescapedDBParameter
        echo $return;
        exit();
    }

}

new Captainform_WP_Submissions();