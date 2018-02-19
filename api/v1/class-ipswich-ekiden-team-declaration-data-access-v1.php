<?php
/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

namespace IpswichEventResultsAPI\V1;
	
require_once plugin_dir_path( __FILE__ ) .'/../config.php';

class Ipswich_Events_Results_Data_Access {		

	private $rdb;

	public function __construct() {
				
		$this->rdb = new \wpdb(EVENTS_RESULTS_DB_USER, EVENTS_RESULTS_DB_PASSWORD, EVENTS_RESULTS_DB_NAME, EVENTS_RESULTS_DB_HOST);		
		$this->rdb->show_errors();
	}
  
  public function getRaceResults($eventId, $year, $leg) {  	
      
      // Ekiden EventId = 4
      $sql = "
			SELECT r.position as position, p.name as runnerName, r.result as time, club.name as club, c.code as categoryCode, r.info as info
			from wp_ije_results r 
			inner join wp_ije_runners p on r.runner_id = p.id
			inner join wp_ije_event_course ec on ec.id = r.course_id
			inner join wp_ije_events e on e.id = ec.event_id
			inner join wp_ije_clubs club on club.id = r.club_id
			left outer join wp_ije_sex s on s.id = p.sex_id
			left outer join wp_ije_category c on r.category_id = c.id
			left outer join wp_ije_event_division ed on r.event_division_id = ed.id 
			WHERE e.id = $eventId AND YEAR(r.racedate) = $year AND ed.id = $leg
			ORDER BY r.position ASC, r.result ASC";
							
			$results = $this->rdb->get_results($sql, OBJECT);

			if ($this->rdb->num_rows == 0)
				return null;
			
			if (!$results)	{			
				return new \WP_Error( 'ipswich_events_results_api_getRaceResults',
						'Unknown error in reading results from the database', array( 'status' => 500 ) );			
			}

			return $results;
		}
	

	}
?>