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

namespace IpswichEkidenTeamDeclaration\V1;

require_once("../../../../../wp-load.php");

class Ipswich_Ekiden_Team_Declaration_Data_Access {		

	global $wpdb;

	public function __construct() {

	}
  
  public function get_clubs() {  	
      
      $sql = "SELECT id, name
              FROM ietd_clubs 
              ORDER BY name";
							
			$results = $wpdb->get_results($sql, OBJECT);
			
			if (!$results)	{			
				return new \WP_Error( 'get_clubs',
						'Unknown error in reading results from the database', array( 'status' => 500 ) );			
			}

			return $results;
	}
  
    public function get_teams() {  	
      
      $sql = "SELECT t.id as id, t.name as name, t.affiliated as isAffiliated, c.name as clubName, 0 as complete
              FROM ietd_teams t
              INNER JOIN ietd_clubs c on c.id = t.club_id
              ORDER BY c.name, t.name
        ";
							
			$results = $wpdb->get_results($sql, OBJECT);
			
			if (!$results)	{			
				return new \WP_Error( 'get_teams',
						'Unknown error in reading results from the database', array( 'status' => 500 ) );			
			}

			return $results;
	}
  
      public function get_team($teamId) {  	
      
      $sql = $wpdb->prepare("SELECT t.id as teamId, t.name as teamName, t.affiliated as isAffiliated, t.club_id as clubId, c.name as clubName, r.id as runnerId, r.name as runnerName, r.gender as gender, r.age_category as ageCategory, tr.leg as leg
            FROM ietd_teams t
            INNER JOIN ietd_clubs c on c.id = t.club_id
            INNER JOIN ietd_team_runners tr ON t.id = tr.team_id
            INNER JOIN ietd_runners r ON r.id = tr.runner_id
            WHERE t.id = %d", $teamId);
							
			$results = $wpdb->get_results($sql, OBJECT);
			
			if (!$results)	{			
				return new \WP_Error( 'get_team',
						'Unknown error in reading results from the database', array( 'status' => 500 ) );			
			}

			return $results;
	}
  
      public function create_team($teamCaptainId, $name, $isAffiliated, $clubId) {  	
      
      $sql = $wpdb->prepare("INSERT INTO ietd_teams(name, affiliated, club_id, captainId) VALUES (%s, %d, %d, %d)", $name, $isAffiliated, $clubId, $teamCaptainId);
							
			$result = $wpdb->query($sql, OBJECT);
			
      if ($result)	{	
        return $this->get_team($this->jdb->insert_id);
      }
      	
			return new \WP_Error( 'create_team',
						'Unknown error in reading results from the database', array( 'status' => 500 ) );			
	}
  
   public function update_team($id, $field, $value) {  	
      
      $result = $wpdb->update( 
					'ietd_teams', 
					array( 
						$field => $value
					), 
					array( 'id' => $id ), 
					array( 
						'%s'
					), 
					array( '%d' ) 
				);

				if ($result)
				{
					return $this->get_team($id);
				}
				
				return new \WP_Error( 'update_team',
						'Unknown error in updating team in to the database'.$sql, array( 'status' => 500 ) );
	}
  
     public function update_team_runner($id, $field, $value) {  	
      
      $result = $wpdb->update( 
					'ietd_runners', 
					array( 
						$field => $value
					), 
					array( 'id' => $id ), 
					array( 
						'%s'
					), 
					array( '%d' ) 
				);

				if ($result)
				{
					return null;
				}
				
				return new \WP_Error( 'update_team',
						'Unknown error in updating team in to the database'.$sql, array( 'status' => 500 ) );
	}
	
    public function delete_team($id) {  	
            
			$sql = $wpdb->prepare('DELETE FROM teams WHERE id = %d;', $id);

			$result = $this->jdb->query($sql);
      	
      if (!$result) {	
			return new \WP_Error( 'delete_team',
						'Unknown error in deleting team from the database', array( 'status' => 500 ) );			
      }
	}

	}
?>