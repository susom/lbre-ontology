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
use phpDocumentor\Reflection\Types\Integer;


//require_once "models/lbre_rooms.php";
require_once "models/Client.php";



class LBRE extends AbstractExternalModule implements \OntologyProvider
{
    use emLoggerTrait;

    CONST DEV_URL = 'https://aswsdev.stanford.edu/LBRE/locations/v1';

    private $client;


    public function __construct()
    {
        parent::__construct();
        // register with OntologyManager
        $manager = \OntologyManager::getOntologyManager();
        $manager->addProvider($this);

    }

    function redcap_module_system_enable($version) {
        \REDCapEntity\EntityDB::buildSchema($this->PREFIX);
    }

    public function redcap_every_page_before_render($project_id)
    { // ???
    }


    function redcap_every_page_top($project_id)
    {
        if (!defined('REDCAP_ENTITY_PREFIX')) {
            $this->emDebug("Delaying execution...");
            $this->delayModuleExecution();

            // Exits gracefully when REDCap Entity is not enabled.
            return;
        }
    }

//    function redcap_entity_types()
//    {
//        $types = [];
//
//        $types['lbre_rooms'] = [ //table name
//            'label' => 'Room',
//            'label_plural' => 'Rooms',
//            'icon' => 'home_pencil',
//            'properties' => [
//                'BUILDINGID' => [
//                    'name' => 'BUILDINGID',
//                    'type' => 'text',
//                    'required' => true,
//                ],
//                'ROOMID' => [
//                    'name' => 'ROOMID',
//                    'type' => 'text',
//                    'required' => true,
//                ],
//                'FLOORID' => [
//                    'name' => 'FLOORID',
//                    'type' => 'text',
//                    'required' => true,
//                ],
//                'ROOMTOTALSQFT' => [
//                    'name' => 'ROOMTOTALSQFT',
//                    'type' => 'text',
//                ],
//                'ORGID' => [
//                    'name' => 'ORGID',
//                    'type' => 'text',
//                ],
//                'DEPARTMENTNAME' => [
//                    'name' => 'DEPARTMENTNAME',
//                    'type' => 'text',
//                ],
//                'DEPTPCT' => [
//                    'name' => 'DEPTPCT',
//                    'type' => 'text',
//                ],
//                'ROOMCAPACITY' => [
//                    'name' => 'ROOMCAPACITY',
//                    'type' => 'text',
//                ],
//                'ROOMDESC' => [
//                    'name' => 'ROOMDESC',
//                    'type' => 'text',
//                ],
//                'FLOORPLANDWG' => [
//                    'name' => 'FLOORPLANDWG',
//                    'type' => 'text',
//                ],
//                'PRIMARYINDIVIDUAL' => [
//                    'name' => 'PRIMARYINDIVIDUAL',
//                    'type' => 'text',
//                ],
//                'PIID' => [
//                    'name' => 'PIID',
//                    'type' => 'text',
//                ],
//                'PIPCT' => [
//                    'name' => 'PIPCT',
//                    'type' => 'text',
//                ],
//                'FUNCTIONALUSECODE' => [
//                    'name' => 'FUNCTIONALUSECODE',
//                    'type' => 'text',
//                ],
//                'FUNCTIONALUSEPCT' => [
//                    'name' => 'FUNCTIONALUSEPCT',
//                    'type' => 'text',
//                ],
//                'ROOMNAME' => [
//                    'name' => 'ROOMNAME',
//                    'type' => 'text',
//                ],
//                'ROOMEFFFROMDATE' => [
//                    'name' => 'ROOMEFFFROMDATE',
//                    'type' => 'text',
//                ],'ROOMEFFTODATE' => [
//                    'name' => 'ROOMEFFTODATE',
//                    'type' => 'text',
//                ],'SQFTCATEGORY' => [
//                    'name' => 'SQFTCATEGORY',
//                    'type' => 'text',
//                ],
//                'ROOMCATEGORYDESCRIPTION' => [
//                    'name' => 'ROOMCATEGORYDESCRIPTION',
//                    'type' => 'text',
//                ],'SPACECOORDINATOR' => [
//                    'name' => 'SPACECOORDINATOR',
//                    'type' => 'text',
//                ],
//                'ROOMSTATUS' => [
//                    'name' => 'ROOMSTATUS',
//                    'type' => 'text',
//                ],
//                'ROOMCLOSEDATE' => [
//                    'name' => 'ROOMCLOSEDATE',
//                    'type' => 'text',
//                ],
//                'ADDRESS1' => [
//                    'name' => 'ADDRESS1',
//                    'type' => 'text',
//                ],
//                'ADDRESS2' => [
//                    'name' => 'ADDRESS2',
//                    'type' => 'text',
//                ],
//                'ROOMALLOCENTERDATE' => [
//                    'name' => 'ROOMALLOCENTERDATE',
//                    'type' => 'text',
//                ],
//                'ROOMALLOCMODIFYDATE' => [
//                    'name' => 'ROOMALLOCMODIFYDATE',
//                    'type' => 'text',
//                ],'ROOMENTERDATE' => [
//                    'name' => 'ROOMENTERDATE',
//                    'type' => 'text',
//                ],'ROOMTYPE' => [
//                    'name' => 'ROOMTYPE',
//                    'type' => 'text',
//                ],'ROOMTYPEDESCRIPTION' => [
//                    'name' => 'ROOMTYPEDESCRIPTION',
//                    'type' => 'text',
//                ],
//                'SRCH1' => [
//                    'name' => 'SRCH1',
//                    'type' => 'text',
//                ],
//
//            ],
//        ];
//
//        $types['lbre_buildings'] = [ //table name
//            'label' => 'Building',
//            'label_plural' => 'Buildings',
//            'icon' => 'home_pencil',
//            'properties' => [
//                'BUILDINGID' => [
//                    'name' => 'BUILDINGID',
//                    'type' => 'text',
//                    'required' => true,
//                ],
//                'NAME' => [
//                    'name' => 'NAME',
//                    'type' => 'text',
//                    'required' => true,
//                ],
//                'STREET' => [
//                    'name' => 'STREET',
//                    'type' => 'text',
//                    'required' => true,
//                ],
//                'CITY' => [
//                    'name' => 'CITY',
//                    'type' => 'text',
//                ],
//                'STATE' => [
//                    'name' => 'STATE',
//                    'type' => 'text',
//                ],
//                'ZIP' => [
//                    'name' => 'ZIP',
//                    'type' => 'text',
//                ],
//                'ZONE' => [
//                    'name' => 'ZONE',
//                    'type' => 'text',
//                ],
//                'BUILDINGTYPE' => [
//                    'name' => 'BUILDINGTYPE',
//                    'type' => 'text',
//                ],
//                'SQUAREFEET' => [
//                    'name' => 'SQUAREFEET',
//                    'type' => 'text',
//                ],
//                'CONSTRUCTIONDATE' => [
//                    'name' => 'CONSTRUCTIONDATE',
//                    'type' => 'text',
//                ],
//                'CLOSEDATE' => [
//                    'name' => 'CLOSEDATE',
//                    'type' => 'text',
//                ],
//                'CREATEDATE' => [
//                    'name' => 'CREATEDATE',
//                    'type' => 'text',
//                ],
//                'MODIFYDATE' => [
//                    'name' => 'MODIFYDATE',
//                    'type' => 'text',
//                ],
//                'IMAGEURL' => [
//                    'name' => 'IMAGEURL',
//                    'type' => 'text',
//                ],
//                'FUNCTIONALUSEPCT' => [
//                    'name' => 'FUNCTIONALUSEPCT',
//                    'type' => 'text',
//                ],
//                'USG' => [
//                    'name' => 'USAGE',
//                    'type' => 'text',
//                ],
//                'PARCEL' => [
//                    'name' => 'PARCEL',
//                    'type' => 'text',
//                ],'LATITUDE' => [
//                    'name' => 'LATITUDE',
//                    'type' => 'text',
//                ],'LONGITUDE' => [
//                    'name' => 'LONGITUDE',
//                    'type' => 'text',
//                ],
//                'STATUS' => [
//                    'name' => 'STATUS',
//                    'type' => 'text',
//                ],
//                'ALIAS' => [
//                    'name' => 'ALIAS',
//                    'type' => 'text',
//                ]
//            ],
//        ];
//
//        return $types;
//    }


    public function initialize(){
        try {
            $settings = $this->getSystemSettings();
            if(!isset($settings['auth-login']) || !isset($settings['auth-password']))
                throw new \Exception('Required authorization credentials not passed in EM settings');

            $client = new Client();
            $client->setEncCredentials($settings['auth-login']['value'], $settings['auth-password']['value']);
            $this->setClient($client);

            // Generate
            $tokenJson = $client->generateBearerToken();
            $this->setProjectSetting('bearer-token', $tokenJson['access_token']);
            $this->setProjectSetting('bearer-expiration', strval(time() + $tokenJson['expires_in']));

        } catch (\Exception $e) {
            $this->emError("Error: $e");
        }
    }

    /**
     * @return Client
     */
    public function getClient(){
        return $this->client;
    }

    /**
     * @param Client $client
     * @return void
     */
    public function setClient(Client $client){
        $this->client = $client;
    }

    /**
     * @return void
     */
//    function importData()
//    {
//        try {
//            $row = 1;
//            $rooms_file = $this->getUrl("assets/lbre_api_rooms.csv");
//            $buildings_file = $this->getUrl("assets/lbre_api_buildings.csv");
//
//            if (($handle = fopen($buildings_file, "r")) !== FALSE) {
//                while (($data = fgetcsv($handle, '', ",")) !== FALSE) {
//                    $row++;
//                    if($row <=2) // skip header
//                        continue;
//                    $this->buildBuildingQuery($data);
//                }
//                fclose($handle);
//            } else {
//                throw new \Exception('Unable to open file');
//            }
//        } catch (\Exception $e) {
//            $this->emError($e);
//            return;
//        }
//
//    }

    public function query($search_term = ""){
        try {
            $settings = $this->getProjectSettings();
            $conditions = [
                !isset($settings['bearer-token']),
                !isset($settings['bearer-expiration']),
                time() > $settings['bearer-expiration']
            ];

            if(array_search(true, $conditions)) //Reset bearer token if past expiration
                $this->initialize();

            $client = new Client();

            $options = [
                'headers' => [
                    'Authorization' => $settings['bearer-token'],
                    'Accept'=> 'application/json'
                ]
            ];

            if($search_term)
                return $client->createRequest("get",self::DEV_URL . "?name=$search_term", $options);
            else
                throw new \Exception('No search term passed');
        } catch (\Exception $e) {
            $this->emError("Error: $e");
        }
    }

//    function buildBuildingQuery($data) {
//        if(length($data) > 0) {
//            try {
//                $created = $updated = time();
//                for($e=0 ; $e < count($data) ; $e++) {
//                    $sql = vsprintf("INSERT INTO redcap_entity_lbre_buildings SET
//                        created = $created,
//                        updated = $updated,
//                        BUILDINGID = '%s',
//                        NAME = '%s',
//                        STREET = '%s',
//                        CITY = '%s',
//                        STATE = '%s',
//                        ZIP = '%s',
//                        ZONE = '%s',
//                       	BUILDINGTYPE = '%s',
//                        SQUAREFEET = '%s',
//                        CONSTRUCTIONDATE='%s',
//                        CLOSEDATE='%s',
//                        CREATEDATE='%s',
//                        MODIFYDATE='%s',
//                        IMAGEURL='%s',
//                        USG='%s',
//                        PARCEL='%s',
//                        LATITUDE='%s',
//                        LONGITUDE='%s',
//                        STATUS='%s'
//                        ",
//                        $data
//                    );
//                }
//                $result = db_query($sql);
//                if(!$result)
//                    throw new \Exception("Error in the following query: $sql");
//            } catch(\Exception $e) {
//                $this->emError($e);
//            }
//        }
//    }

//    function buildRoomsQuery($data) {
//        if(length($data) > 0) {
//            try {
//                $created = $updated = time();
//
//                for($e=0 ; $e < count($data) ; $e++) {
//                    $sql = vsprintf("INSERT INTO redcap_entity_lbre_rooms SET
//                        created = $created,
//                        updated = $updated,
//                        BUILDINGID = '%s',
//                        ROOMID = '%s',
//                        FLOORID = '%s',
//                        ROOMTOTALSQFT = '%s',
//                        ORGID = '%s',
//                        DEPARTMENTNAME = '%s',
//                        DEPTPCT = '%s',
//                       	ROOMCAPACITY = '%s',
//                        ROOMDESC = '%s',
//                        FLOORPLANDWG = '%s',
//                        PRIMARYINDIVIDUAL = '%s',
//                        PIID = '%s',
//                        PIPCT = '%s',
//                        FUNCTIONALUSECODE = '%s',
//                        FUNCTIONALUSEPCT = '%s',
//                        ROOMNAME = '%s',
//                        ROOMEFFFROMDATE = '%s',
//                        ROOMEFFTODATE = '%s',
//                        SQFTCATEGORY = '%s',
//                        ROOMCATEGORYDESCRIPTION = '%s',
//                        SPACECOORDINATOR = '%s',
//                        ROOMSTATUS = '%s',
//                        ROOMCLOSEDATE = '%s',
//                        ADDRESS1 = '%s',
//                        ADDRESS2 = '%s',
//                        ROOMALLOCENTERDATE = '%s',
//                        ROOMALLOCMODIFYDATE = '%s',
//                        ROOMENTERDATE = '%s',
//                        ROOMTYPE = '%s',
//                        ROOMTYPEDESCRIPTION = '%s',
//                        SRCH1 = '%s'",
//                        $data
//                    );
//                }
//                db_query($sql);
//            } catch(\Exception $e) {
//                $this->emError($e);
//            }
//        }
//    }


    /**
     * Search API with a search term for a given ontology
     * Returns array of results with Notation as key and PrefLabel as value.
     */
    public function searchOntology($category, $search_term, $result_limit)
    {
        $res = $this->query($search_term);
        $values = array();
        foreach($res['buildings'] as $k){
            $temp = [
                'code' => $k['id'],
                'display' => $k['name'],
                'active'=> 'true'
            ];
            array_push($values, $temp);
        }
//        $values = [
//            0 => [
//                'code'=> "fan",
//                'display'=> 'Robert Fanning',
//                'active'=> 'null',
//            ]
//        ];

//        foreach ($systemCategories as $cat) {
//            $categories[$cat['category']] = $cat;
//        }
//        foreach ($projectCategories as $cat) {
//            $categories[$cat['category']] = $cat;
//        }

//        $values = array();
//        $categoryData = $categories[$category];
//        if ($categoryData) {
//            $type = $categoryData['values-type'];
//            $rawValues = $categoryData['values'];
//
//
//            if ($type == 'list') {
//                $list = preg_split("/\r\n|\n|\r/", $rawValues);
//                foreach ($list as $item) {
//                    $active = true;
//                    if (strncmp($item, "\\!", 2) === 0) {
//                        // \! escaped !
//                        $item = substr($item, 1); // remove leading \
//                    } else if (strncmp($item, "!", 1) === 0) {
//                        // not active
//                        $item = substr($item, 1);  // remove leading !
//                        $active = false;
//                    }
//                    $values[] = ['code' => $item, 'display' => $item, 'active' => $active];
//                }
//            } elseif ($type == 'bar') {
//                $rows = preg_split("/\r\n|\n|\r/", $rawValues);
//                foreach ($rows as $row) {
//                    $cols = explode('|', $row);
//                    $col_rev = array_reverse($cols);
//                    $code = array_pop($col_rev);
//                    $active = true;
//                    if (strncmp($code, "\\!", 2) === 0) {
//                        // \! escaped !
//                        $code = substr($code, 1); // remove leading \
//                    } else if (strncmp($code, "!", 1) === 0) {
//                        // not active
//                        $code = substr($code, 1);  // remove leading !
//                        $active = false;
//                    }
//                    $values[] = ['code' => $code, 'display' => array_pop($col_rev), 'active' => $active, 'synonyms' => $col_rev];
//                }
//            } elseif ($type == 'json') {
//                $list = json_decode($rawValues, true);
//                if (is_array($list)) {
//                    foreach ($list as $item) {
//                        if (isset($item['code']) and isset($item['display'])) {
//                            $values[] = ['code' => $item['code'], 'display' => $item['display'], 'active' => $item['active'], 'synonyms' => $item['synonyms']];
//                        }
//                    }
//                }
//            }
//        }
        //error_log(print_r($values, TRUE));
        $wordResults = array();
        $strippedSearchTerm = $this->skip_accents($search_term);

        if (strlen($strippedSearchTerm) > 0 && ($strippedSearchTerm[0] == "'" || $strippedSearchTerm[0] == '"')) {
            $searchWords = [substr($strippedSearchTerm, 1)];
        } else {
            $searchWords = explode(' ', $strippedSearchTerm);
        }

        foreach ($values as $val) {
            if ($val['active'] === false) {
                // marked as inactive
                continue;
            }
            $code = $val['code'];
//            if (in_array($code, $hideChoice)){
//                // in hide choice list
//                continue;
//            }
            $desc = $val['display'];
            $synonyms = $val['synonyms'];
            $strippedDesc = $this->skip_accents($desc);
            $foundCount = 0;
            $minPos = 99;
            foreach ($searchWords as $word) {
                $pos = stripos($strippedDesc, $word);
                if ($pos !== FALSE) {
                    $foundCount++;
                    if ($pos < $minPos) {
                        $minPos = $pos;
                    }
                }
            }
//            if ($synonyms) {
//                foreach ($synonyms as $synonym) {
//                    $synonymStrippedDesc = $this->skip_accents($synonym);
//                    $synonymFoundCount = 0;
//                    $synonymMinPos = 99;
//                    foreach ($searchWords as $word) {
//                        $synonymPos = stripos($synonymStrippedDesc, $word);
//                        if ($synonymPos !== FALSE) {
//                            $synonymFoundCount++;
//                            if ($synonymPos < $synonymMinPos) {
//                                $synonymMinPos = $synonymPos;
//                            }
//                        }
//                    }
//                    if ($synonymFoundCount > $foundCount) {
//                        $foundCount = $synonymFoundCount;
//                        $minPos = $synonymMinPos;
//                    } else if ($synonymFoundCount == $foundCount && $synonymMinPos < $minPos) {
//                        $minPos = $synonymMinPos;
//                    }
//                }
//            }
            if ($foundCount > 0) {
                $wordResults[] = array('foundCount' => $foundCount, 'minPos' => $minPos, 'value' => $val);
            }
        }
        $fcColumn = array_column($wordResults, 'foundCount');
        $posColumn = array_column($wordResults, 'minPos');

        // sort on word match count then on closest to start of string
        array_multisort($fcColumn, SORT_DESC, $posColumn, SORT_ASC, $wordResults);
        $mresults = array_column($wordResults, 'value');

        $results = array();
        foreach ($mresults as $val) {
            // make sure result is escaped..
            $code = \REDCap::escapeHtml($val['code']);
            $desc = \REDCap::escapeHtml($val['display']);
            $results[$code] = $desc;
        }

        $result_limit = (is_numeric($result_limit) ? $result_limit : 20);
        if(count($results) === 0)
            $results['__NR__'] = 'none';
//        if (count($results) < $result_limit) {
        // add no results found
//            $return_no_result = $categoryData['return-no-result'];
//            if ($return_no_result) {
//                $no_result_label = $categoryData['no-result-label'];
//                $no_result_code = $categoryData['no-result-code'];
//            $results['__NR__'] = 'none';
//            }
//        }

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

        $categories = ["0" => "hello"];
//        foreach ($systemCategories as $cat) {
//            $categories[$cat['category']] = $cat;
//        }
//        foreach ($projectCategories as $cat) {
//            $categories[$cat['category']] = $cat;
//        }

        $categoryList = '';
        foreach ($categories as $k => $v) {
            $categoryList .= "<option value='{$k}'>{$v}</option>\n";
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
        return 'nice';
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
}
