<?php
/**
 *
 * CSIRO Open Source Software Licence Agreement (variation of the BSD / MIT License)
 * Copyright (c) 2018, Commonwealth Scientific and Industrial Research Organisation (CSIRO) ABN 41 687 119 230.
 * All rights reserved. CSIRO is willing to grant you a licence to this SimpleOntologyExternalModule on the following terms, except where otherwise indicated for third party material.
 * Redistribution and use of this software in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * Neither the name of CSIRO nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission of CSIRO.
 * EXCEPT AS EXPRESSLY STATED IN THIS AGREEMENT AND TO THE FULL EXTENT PERMITTED BY APPLICABLE LAW, THE SOFTWARE IS PROVIDED "AS-IS". CSIRO MAKES NO REPRESENTATIONS, WARRANTIES OR CONDITIONS OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO ANY REPRESENTATIONS, WARRANTIES OR CONDITIONS REGARDING THE CONTENTS OR ACCURACY OF THE SOFTWARE, OR OF TITLE, MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, NON-INFRINGEMENT, THE ABSENCE OF LATENT OR OTHER DEFECTS, OR THE PRESENCE OR ABSENCE OF ERRORS, WHETHER OR NOT DISCOVERABLE.
 * TO THE FULL EXTENT PERMITTED BY APPLICABLE LAW, IN NO EVENT SHALL CSIRO BE LIABLE ON ANY LEGAL THEORY (INCLUDING, WITHOUT LIMITATION, IN AN ACTION FOR BREACH OF CONTRACT, NEGLIGENCE OR OTHERWISE) FOR ANY CLAIM, LOSS, DAMAGES OR OTHER LIABILITY HOWSOEVER INCURRED.  WITHOUT LIMITING THE SCOPE OF THE PREVIOUS SENTENCE THE EXCLUSION OF LIABILITY SHALL INCLUDE: LOSS OF PRODUCTION OR OPERATION TIME, LOSS, DAMAGE OR CORRUPTION OF DATA OR RECORDS; OR LOSS OF ANTICIPATED SAVINGS, OPPORTUNITY, REVENUE, PROFIT OR GOODWILL, OR OTHER ECONOMIC LOSS; OR ANY SPECIAL, INCIDENTAL, INDIRECT, CONSEQUENTIAL, PUNITIVE OR EXEMPLARY DAMAGES, ARISING OUT OF OR IN CONNECTION WITH THIS AGREEMENT, ACCESS OF THE SOFTWARE OR ANY OTHER DEALINGS WITH THE SOFTWARE, EVEN IF CSIRO HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH CLAIM, LOSS, DAMAGES OR OTHER LIABILITY.
 * APPLICABLE LEGISLATION SUCH AS THE AUSTRALIAN CONSUMER LAW MAY APPLY REPRESENTATIONS, WARRANTIES, OR CONDITIONS, OR IMPOSES OBLIGATIONS OR LIABILITY ON CSIRO THAT CANNOT BE EXCLUDED, RESTRICTED OR MODIFIED TO THE FULL EXTENT SET OUT IN THE EXPRESS TERMS OF THIS CLAUSE ABOVE "CONSUMER GUARANTEES".  TO THE EXTENT THAT SUCH CONSUMER GUARANTEES CONTINUE TO APPLY, THEN TO THE FULL EXTENT PERMITTED BY THE APPLICABLE LEGISLATION, THE LIABILITY OF CSIRO UNDER THE RELEVANT CONSUMER GUARANTEE IS LIMITED (WHERE PERMITTED AT CSIRO'S OPTION) TO ONE OF FOLLOWING REMEDIES OR SUBSTANTIALLY EQUIVALENT REMEDIES:
 * (a)               THE REPLACEMENT OF THE SOFTWARE, THE SUPPLY OF EQUIVALENT SOFTWARE, OR SUPPLYING RELEVANT SERVICES AGAIN;
 * (b)               THE REPAIR OF THE SOFTWARE;
 * (c)               THE PAYMENT OF THE COST OF REPLACING THE SOFTWARE, OF ACQUIRING EQUIVALENT SOFTWARE, HAVING THE RELEVANT SERVICES SUPPLIED AGAIN, OR HAVING THE SOFTWARE REPAIRED.
 * IN THIS CLAUSE, CSIRO INCLUDES ANY THIRD PARTY AUTHOR OR OWNER OF ANY PART OF THE SOFTWARE OR MATERIAL DISTRIBUTED WITH IT.  CSIRO MAY ENFORCE ANY RIGHTS ON BEHALF OF THE RELEVANT THIRD PARTY.
 * Third Party Components
 * The following third party components are distributed with the Software.  You agree to comply with the licence terms for these components as part of accessing the Software.  Other third party software may also be identified in separate files distributed with the Software.
 *
 *
 *
 */

namespace Stanford\LBRE;
include "emLoggerTrait.php";

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use Exception;

require_once "models/Client.php";

class LBRE extends AbstractExternalModule implements \OntologyProvider
{
    use emLoggerTrait;

    private $client;

    public function __construct()
    {
        parent::__construct();
        // register with OntologyManager
        $manager = \OntologyManager::getOntologyManager();
        $manager->addProvider($this);

    }

    public function redcap_every_page_before_render($project_id)
    {
    }

    public function redcap_survey_page_top()
    {
        $table = $this->createActionTable();
        $this->injectJavascript($table);
    }

    public function redcap_data_entry_form_top()
    {
        $table = $this->createActionTable();
        $this->injectJavascript($table);
    }

    public function initialize()
    {

        try {
            $this->disableUserBasedSettingPermissions();
            $settings = $this->getSystemSettings();
            global $Proj;

            if (!isset($settings['auth-login']) || !isset($settings['auth-password']))
                throw new \Exception('Required authorization credentials not passed in EM settings');

            $client = new Client($this);
            $client->setEncCredentials($settings['auth-login']['system_value'], $settings['auth-password']['system_value']);
            $this->setClient($client);

            $tokenJson = $client->generateBearerToken($settings['auth-url']['system_value']);

            if(empty($tokenJson)){
                throw new \Exception('Error : bearer token not generated correctly');
            }

            $this->setSystemSetting('bearer-token', $tokenJson['access_token']);
            $this->setSystemSetting('bearer-expiration', strval(time() + $tokenJson['expires_in']));

        } catch (\Exception $e) {
            \REDCap::logEvent("Error: $e");
            $this->emError("Error: $e");
        }
    }


    /**
     * Creates a Key, Value array of action tags and their reference fields to inject upon page load
     * @return array [filteredField => filterByField, ... ]
     */
    public function createActionTable()
    {
        global $Proj;
        $table = [];
        foreach ($Proj->metadata as $field) {
            if (!empty($field['misc']) && str_contains($field['misc'], "filterby=")) {
                $output = preg_split('/[\s,\n\r]+/', $field['misc']);
                $filterField = null;
                foreach ($output as $action)
                    if (str_contains($action, "filterby="))
                        $filterField = explode("=", $action)[1];
                $table[$field['field_name']] = $filterField;
            }
        }
        return $table;
    }

    /**
     * Injects javascript files and any necessary data necessary before page load
     * @param $bulk Data to be encoded
     * @return void
     */
    public function injectJavascript($bulk = [])
    {
        try {

            $encoded = json_encode($bulk);
            $jsFilePath = $this->getUrl('scripts/override.js');
            print "<script type='text/javascript'>var actionTagTable = $encoded; </script>";;
            print "<script type='module' src=$jsFilePath></script>";

        } catch (\Exception $e) {
            \REDCap::logEvent("Error: $e");
            $this->emError($e);
        }
    }

    /**
     * @param $category | Category of ontology to search
     * @param $search_term
     * @return mixed|string|void
     */
    public function sendQuery($category, $search_term = "", $filter = null)
    {
        try {
            if (empty($search_term))
                throw new \Exception('No search term passed');

            $settings = $this->getSystemSettings();
            if(!isset($settings['bearer-token'])
                || !isset($settings['bearer-token']['system_value'])
                || !isset($settings['bearer-expiration']['system_value'])
                || time() > $settings['bearer-expiration']['system_value']
            ){
                $this->initialize();
                $settings = $this->getSystemSettings();
            }
//            $conditions = [
//                !isset($settings['bearer-token']['system_value']),
//                !isset($settings['bearer-expiration']['system_value']),
//                time() > $settings['bearer-expiration']['system_value']
//            ];

//            if (in_array(true, $conditions)) { //Reset bearer token if past expiration, reset
//                $this->initialize();
//                $settings = $this->getSystemSettings();
//            }
            if(isset($settings['bearer-token'])) {
                $client = new Client($this);

                $options = [
                    'headers' => [
                        'Authorization' => $settings['bearer-token']['system_value'],
                        'Accept' => 'application/json'
                    ]
                ];

                $url = $this->getQueryUrl($category, $search_term, $filter);

                return $client->createRequest("get", $url, $options);
            } else {
                throw new \Exception('Bearer token failed to be created on second attempt');
            }


        } catch (\Exception $e) {
            \REDCap::logEvent("Error: $e");
            $this->emError("Error: $e");
        }
    }

    /**
     * Generates API url based on system and project setting
     * @param $category
     * @param $search_term
     * @param $filter - building ID if request is client based
     * @return string
     * @throws \Exception
     */
    public function getQueryUrl($category, $search_term, $filter)
    {
        $pSettings = $this->getProjectSettings();
        $sSettings = $this->getSystemSettings();

        $url = $sSettings['query-url']['system_value'];

        if (!isset($url))
            throw new \Exception('Empty url in system settings');

        $term = strtolower(urlencode(htmlspecialchars($search_term, ENT_NOQUOTES)));

        if (strtolower($category) === 'buildings') {
            $url .= "locations/v1?srch1=$term";
        } elseif (strtolower($category) === 'rooms') {
            if (isset($filter) && !empty($filter)) { //Request is initiated via autocomplete param on frontend, no save hook
                $filter = urlencode(filter_var($filter, FILTER_SANITIZE_STRING));
                $url .= "rooms/v1?srch1=$term&building=$filter";
            } else { //Backend filtering
                $filterBy = $this->parseSmartVariable();
                if (isset($filterBy)) { // User wants to filter room by building ID
                    $ref_url = parse_url($_SERVER['HTTP_REFERER']);
                    parse_str($ref_url['query'], $params); //Get specific record_id
                    $record_data = json_decode(\REDCap::getData('json', $params['id'], $filterBy));

                    if (!empty($record_data)) { //Specific search by building
                        $buildingId = $record_data[0]->$filterBy;
                        $url .= "rooms/v1?srch1=$term&building=$buildingId";
                    } else { //Else regular search
                        $url .= "rooms/v1?srch1=$term";
                    }
                } else {
                    $url .= "rooms/v1?srch1=$term";
                }
            }

        } else {
            throw new \Exception("EM must be configured using either location or room ontology, it is currently : $category");
        }

        if (isset($pSettings['result-count'])) {
            $count = $pSettings['result-count'];
            return $url .= "&perPage=$count";
        } else {
            return $url;
        }

    }

    /**
     * @return string|null
     */
    public function parseSmartVariable()
    {
        global $Proj;
        foreach ($Proj->metadata as $field) {
            if ($field['field_name'] === $_GET['field'] && str_contains($field['misc'], "filterby=")) {
                $output = preg_split('/[\s,\n\r]+/', $field['misc']);
                $filterField = null;
                foreach ($output as $action)
                    if (str_contains($action, "filterby="))
                        $filterField = explode("=", $action)[1];

                return $filterField;
            }
        }
        return null;
    }

    /**
     * Search API with a search term for a given ontology
     * Returns array of results
     */
    public function searchOntology($category, $search_term, $result_limit)
    {
        $res = $this->sendQuery($category, $search_term, $_GET['clientFilter'] ?? null);
        $values = array();

        if (strtolower($category) === 'buildings') {
            foreach ($res['buildings'] as $k) {
                $temp = [
                    'code' => $k['id'],
                    'display' => $k['name'],
                    'active' => 'true'
                ];
                $values[] = $temp;
            }
        } elseif (strtolower($category) === 'rooms') {
            foreach ($res['buildings'] as $k) {
//                if ($k['roomStatus'] === 'INACTIVE' || $k['roomCategoryDesc'] === 'UNASSIGNABLE AREAS')
//                if ($k['roomCategoryDesc'] === 'UNASSIGNABLE AREAS')
//                    continue;

                $temp = [
                    'code' => $k['roomID'],
                    'display' => $k['roomName'] ?? $k['roomTypeDesc'] . " - " . $k['roomCategoryDesc'],
                    'active' => 'true'
                ];
                $values[] = $temp;
            }
        } else {
            \REDCap::logEvent("Error: Category specified by search was not set correctly");
            $this->emError('Category specified by search was not set correctly');
//            $this->exitAfterHook();
        }

        $results = array();

        foreach ($values as $val) {
            // make sure result is escaped..
            $code = \REDCap::escapeHtml($val['code']);
            $desc = \REDCap::escapeHtml($val['display']);
            $results[$code] = $desc;
        }
        $result_limit = (is_numeric($result_limit) ? $result_limit : 30);

        if (count($results) === 0)
            $results['__NR__'] = 'No Results';

        // Return array of results
        return array_slice($results, 0, $result_limit, true);
    }

    /**
     * return the name of the ontology service as it will be display on the service selection
     * drop down.
     */
    public function getProviderName()
    {
        return 'Site Defined Ontologies';
    }


    /**
     * return the prefex used to denote ontologies provided by this provider.
     */
    public function getServicePrefix()
    {
        return 'SIMPLE';
    }

    /**
     * Return a string which will be placed in the online designer for
     * selecting an ontology for the service.
     * When an ontology is selected it should make a javascript call to
     * update_ontology_selection($service, $category)
     *
     * The provider may include a javascript function
     * <service>_ontology_changed(service, category)
     * which will be called when the ontology selection is changed. This function
     * would update any UI elements is the service matches or clear the UI elemements
     * if they do not.
     */
    public function getOnlineDesignerSection()
    {
        $systemCategories = $this->getSystemCategories();
        $projectCategories = $this->getProjectCategories();

        $categories = [];
        foreach ($systemCategories as $cat) {
            $categories[$cat['category']] = $cat;
        }
        foreach ($projectCategories as $cat) {
            $categories[$cat['category']] = $cat;
        }

        $categoryList = '';
        foreach ($categories as $cat) {
            $category = $cat['category'];
            $name = $cat['name'];
            $categoryList .= "<option value='{$category}'>{$category}</option>\n";
        }

        $onlineDesignerHtml = <<<EOD
<script type="text/javascript">
  function SIMPLE_ontology_changed(service, category){
    var newSelection = ('SIMPLE' == service) ? category : '';
    $('#simple_ontology_category').val(newSelection);
  }

</script>
<div style='margin-bottom:3px;'>
  Select Local Ontology to use:
</div>
<select id='simple_ontology_category' name='simple_ontology_category'
            onchange="update_ontology_selection('SIMPLE', this.options[this.selectedIndex].value)"
            class='x-form-text x-form-field' style='width:330px;max-width:330px;'>
        {$categoryList}
</select>
EOD;
        return $onlineDesignerHtml;
    }


    function skip_accents($str, $charset = 'utf-8')
    {

        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str);

        return $str;
    }


    function getSystemCategories()
    {
        $key = 'site-category-list';
        $keys = ['site-category' => 'category',
            'site-name' => 'name',
            'site-search-type' => 'search-type',
            'site-return-no-result' => 'return-no-result',
            'site-no-result-label' => 'no-result-label',
            'site-no-result-code' => 'no-result-code',
            'site-values-type' => 'values-type',
            'site-values' => 'values'];
        $subSettings = [];
        $rawSettings = $this->getSubSettings($key);
        //error_log("system_settings = ".print_r($rawSettings, TRUE));
        foreach ($rawSettings as $data) {
            $subSetting = [];
            foreach ($keys as $k => $nk) {
                $subSetting[$nk] = $data[$k];
            }
            $subSettings[] = $subSetting;
        }
        return $subSettings;
    }

    function getProjectCategories()
    {
        $key = 'project-category-list';
        $keys = ['project-category' => 'category',
            'project-name' => 'name',
            'project-search-type' => 'search-type',
            'project-return-no-result' => 'return-no-result',
            'project-no-result-label' => 'no-result-label',
            'project-no-result-code' => 'no-result-code',
            'project-values-type' => 'values-type',
            'project-values' => 'values'];
        $subSettings = [];
        $rawSettings = $this->getSubSettings($key);
        //error_log("project_settings = ".print_r($rawSettings, TRUE));
        foreach ($rawSettings as $data) {
            $subSetting = [];
            foreach ($keys as $k => $nk) {
                $subSetting[$nk] = $data[$k];
            }
            $subSettings[] = $subSetting;
        }
        return $subSettings;
    }

    /**
     *  Takes the value and gives back the label for the value.
     */
    public function getLabelForValue($category, $value)
    {
        $systemCategories = $this->getSystemCategories();
        $projectCategories = $this->getProjectCategories();
        $categories = [];
        foreach ($systemCategories as $cat) {
            $categories[$cat['category']] = $cat;
        }
        foreach ($projectCategories as $cat) {
            $categories[$cat['category']] = $cat;
        }

        $values = array();
        $categoryData = $categories[$category];
        if ($categoryData) {
            $type = $categoryData['values-type'];
            $rawValues = $categoryData['values'];


            if ($type == 'list') {
                $list = preg_split("/\r\n|\n|\r/", $rawValues);
                foreach ($list as $item) {
                    $values[] = ['code' => $item, 'display' => $item];
                }
            } elseif ($type == 'bar') {
                $rows = preg_split("/\r\n|\n|\r/", $rawValues);
                foreach ($rows as $row) {
                    $cols = explode('|', $row);
                    $values[] = ['code' => $cols[0], 'display' => $cols[1]];
                }
            } elseif ($type == 'json') {
                $list = json_decode($rawValues, true);
                if (is_array($list)) {
                    foreach ($list as $item) {
                        if (isset($item['code']) and isset($item['display'])) {
                            $values[] = ['code' => $item['code'], 'display' => $item['display']];
                        }
                    }
                }
            }
            if (array_key_exists($value, $values)) {
                return $values[$value];
            }
        }
        return $value;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     * @return void
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }
}
