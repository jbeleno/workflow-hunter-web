<?php
/**
 * WorkflowHunt
 *
 * A semantic search engine for scientific workflow repositories
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2016 - 2017, Juan Sebastián Beleño Díaz
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	WorkflowHunt
 * @author	Juan Sebastián Beleño Díaz
 * @copyright	Copyright (c) 2016 - 2017, Juan Sebastián Beleño Díaz
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://github.com/jbeleno
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WorkflowHunt Workflow Model
 *
 * @category	Models
 * @author		Juan Sebastián Beleño Díaz
 * @link		xxx
 */
class Workflow_model extends CI_Model {

	/**
	 * Workflow identifier
	 *
	 * @var	int
	 */
	public $id;

	/**
	 * Workflow title
	 *
	 * @var	string
	 */
	public $title;

	/**
	 * Workflow description
	 *
	 * @var	string
	 */
	public $description;

	/**
	 * Workflow tags
	 *
	 * @var	array
	 */
	public $tags;

	/**
	 * Workflow API URL 
	 *
	 * @var	string
	 */
	private $API_URL = "http://www.myexperiment.org/workflows.xml";

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
    {
        // Call the CI_Model constructor
        parent::__construct();
    }

    // --------------------------------------------------------------------

    /**
	 * Insert Workflow Identifiers in Database
	 *
	 * @param	int	$wf_per_page	Number of workflows per page in the API
	 * @return	void
	 */
    public function insert_workflow_ids($wf_per_page = 50)
    {
    	$flag = true;
    	$page = 1;
    	$workflows = array();

    	while($flag)
    	{
    		// Construct dinamically a URL until reach all the workflows
    		$PARAMS = "sort=id&num=".$wf_per_page."&page=".$page;
    		$url = $API_URL."?".$PARAMS;

    		// Request the content in XML format
    		$context  = stream_context_create(
    						array(
    							'http' => array(
    										'header' => 'Accept: application/xml'
    										)
    							)
    						);

			$xml = file_get_contents($url, false, $context);

			if($xml = simplexml_load_string($xml))
			{
				// If the content is converted into XML, we'll create the array
				// of workflow ids
				foreach ($xml->children() as $workflow) 
				{
					$workflows[] = array(
										'id' => $workflow['id'],
										'date' => date("Y-m-d H:i:s"),
										'date_last_update' => date("Y-m-d H:i:s")
									);
				}
			}
			else
			{
				// If the content isn't converted into XML, we'll exit the loop
				$flag = false;
			}

    		$page++;
    	}

    	$this->db->insert_batch('workflow', $workflows);
    }

    // --------------------------------------------------------------------

    /**
	 * Update Workflow Metadata in Database
	 *
	 * The workflow metadata is extracted from the API and stored in the 
	 * database. Nevertheless, tags and workflow metadata are store in 
	 * different tables. 
	 *
	 * @return	void
	 */
    public function update_workflow_metadata()
    {
    	$this->db->select('id');
    	$query = $this->db->get('workflow');

    	$workflows = array();
    	$tags = array();
    	$tag_wf = array();

    	$arr_tags = array();

    	foreach ($query->result() as $workflow) 
    	{
    		$id_workflow = $workflow->id;

    		// Construct dinamically a URL for each workflow
    		$PARAMS = "id=".$id_workflow."&elements=title,description,tags";
    		$url = $API_URL."?".$PARAMS;

    		// Request the content in XML format
    		$context  = stream_context_create(
    						array(
    							'http' => array(
    										'header' => 'Accept: application/xml'
    										)
    							)
    						);

			$xml = file_get_contents($url, false, $context);

			if($xml = simplexml_load_string($xml))
			{
				// If the content is converted into XML, we'll update the 
				// workflow metadata
				$workflows[] = array(
										'id' => $id_workflow,
										'title' => $xml->title,
										'description' => $xml->description,
										'date_last_update' => date("Y-m-d H:i:s")
									);

				foreach ($xml->tags as $tag) 
				{
					// Insert tags that belong to workflows
					if(!in_array($tag, $arr_tags))
					{
						$arr_tags[] = $tag;
						$tags[] = array(
									'id' => $tag['id'], 
									'name' => $tag,
									'date' => date("Y-m-d H:i:s")
								  );
					}

					$tag_wf[] = array(
									'tag_id' => $tag['id'], 
									'workflow_id' => $id_workflow,
									'date' => date("Y-m-d H:i:s")
								  );

				}
			}
    	}

    	$this->db->insert_batch('tag', $tags);
    	$this->db->insert_batch('tag_wf', $tag_wf);

    	$this->db->update_batch('workflow', $workflows, 'id');
    }

}

/* End of file Workflow_model.php */
/* Location: ./application/models/Workflow_model.php */