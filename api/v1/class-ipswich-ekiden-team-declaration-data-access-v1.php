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

class Ipswich_Ekiden_Team_Declaration_Data_Access {		

	private $db;
  
  const Unattached = 989;

	public function __construct() {
		$this->db = $GLOBALS['wpdb'];
	}
  
  public function get_clubs() {  	
      
      $sql = "SELECT id, name
              FROM ietd_clubs 
              ORDER BY name";
							
			$results = $this->db->get_results($sql, OBJECT);
      
      if ($this->db->num_rows == 0)
				return array();
			
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
							
			$results = $this->db->get_results($sql, OBJECT);
      
      if ($this->db->num_rows == 0)
				return array();
			
			if (!$results)	{			
				return new \WP_Error( 'get_teams',
						'Unknown error in reading results from the database', array( 'status' => 500 ) );			
			}

			return $results;
	}
	
	    public function get_myteams($captainId) {  	
      
      $sql = $this->db->prepare("SELECT t.id as id, t.name as name, t.affiliated as isAffiliated, c.name as clubName, 0 as complete
              FROM ietd_teams t
              INNER JOIN ietd_clubs c on c.id = t.club_id
			  WHERE t.captain_id = %d
              ORDER BY c.name, t.name
        ", $captainId);
							
			$results = $this->db->get_results($sql, OBJECT);
      
      if ($this->db->num_rows == 0)
				return array();
			
			if (!$results)	{			
				return new \WP_Error( 'get_teams',
						'Unknown error in reading results from the database', array( 'status' => 500 ) );			
			}

			return $results;
	}
  
      public function get_team($teamId) {  	
       $sql = $this->db->prepare("SELECT t.id as id, t.name as name, t.affiliated as isAffiliated, c.name as clubName, t.captain_id as captainId
              FROM ietd_teams t
              INNER JOIN ietd_clubs c on c.id = t.club_id
			  WHERE t.id = %d
        ", $teamId);
		  
		  $team = $this->db->get_row($sql);
		  
      $sql = $this->db->prepare("SELECT r.id as id, r.name as name, r.gender as gender, r.age_category as ageCategory, tr.leg as leg
            FROM ietd_team_runners tr
            INNER JOIN ietd_runners r ON r.id = tr.runner_id
            WHERE tr.team_id = %d", $teamId);
							
			$runners = $this->db->get_results($sql, OBJECT);          
      
      $team->runners = $runners;
         
			return $team;
	}
  
      public function create_team($teamCaptainId, $name, $isAffiliated, $clubId) {  	
      
      $sql = $this->db->prepare("INSERT INTO ietd_teams(name, affiliated, club_id, captain_id) VALUES (%s, %d, %d, %d)", $name, $isAffiliated, $clubId, $teamCaptainId);
							
			$result = $this->db->query($sql, OBJECT);
			
      if ($result)	{	
        return $this->get_team($this->db->insert_id);
      }
      	
			return new \WP_Error( 'create_team',
						'Unknown error in reading results from the database', array( 'status' => 500, 'sql' => $sql ) );			
	}
  
   public function update_team($id, $field, $value) {  	     
      
      switch ($field) {
        case "name":
             $sql = $this->db->prepare("UPDATE ietd_teams SET name = '%s' WHERE id = %d", $value, $id);             
            break;
        case "clubId":
            if ($value == self::Unattached) {
              $sql = $this->db->prepare("UPDATE ietd_teams SET club_id = %d, affiliated = 0 WHERE id = %d", $value, $id);
            } else {
              $sql = $this->db->prepare("UPDATE ietd_teams SET club_id = %d, affiliated = 1 WHERE id = %d", $value, $id);
            }            
            break;
    }
    
    $result = $this->db->query($sql, OBJECT);
        
		return $this->get_team($id);
	}
  
        public function add_team_runner($teamId, $leg, $name, $genderId, $ageCategory) {  	
      
      $sql = $this->db->prepare("INSERT INTO ietd_runners(name, age_category, gender) VALUES (%s, %s, %d)", $name, $ageCategory, $genderId);
							
			$result = $this->db->query($sql, OBJECT);
			
      if ($result)	{	
      
         $sql = $this->db->prepare("INSERT INTO ietd_team_runners(team_id, runner_id, leg) VALUES (%d, %d, %d)", $teamId, $this->db->insert_id, $leg);
         
         $result = $this->db->query($sql, OBJECT);
         
         if (!$result) {
         return new \WP_Error( 'add_team_runner',
						'Unknown error in updating team in to the database', array( 'status' => 500 ) );
         }
      }
      	
			return;			
	}
  
     public function update_team_runner($teamId, $leg, $field, $value) {  	
       switch ($field) {
        case "name": 
          $sql = $this->db->prepare("UPDATE ietd_team_runners tr, ietd_runners r
                                     SET r.name = '%s' 
                                     WHERE tr.runner_id = r.id AND tr.leg = %d AND tr.team_id = %d", $value, $leg, $teamId);
          break;
        case "ageCategory": 
          $sql = $this->db->prepare("UPDATE ietd_team_runners tr, ietd_runners r
                                     SET r.age_category = '%s' 
                                     WHERE tr.runner_id = r.id AND tr.leg = %d AND tr.team_id = %d", $value, $leg, $teamId);
          break;
        case "gender": 
          $sql = $this->db->prepare("UPDATE ietd_team_runners tr, ietd_runners r
                                     SET r.gender = %d 
                                     WHERE tr.runner_id = r.id AND tr.leg = %d AND tr.team_id = %d", $value, $leg, $teamId);
          break;
        default:
          return new \WP_Error( 'update_team_runner',
						'Invalid request', array( 'status' => 400 ) );         
       }
				
			$result = $this->db->query($sql, OBJECT);
         
         if (!$result) {
         return new \WP_Error( 'update_team_runner',
						'Unknown error in updating team in to the database', array( 'status' => 500 ) );
         }
	}
	
    public function delete_team($id) {  	
            
			$sql = $this->db->prepare("DELETE FROM ietd_teams WHERE id = %d", $id);

			$result = $this->db->query($sql);
      	
      if (!$result) {	
			return new \WP_Error( 'delete_team',
						'Unknown error in deleting team from the database', array( 'status' => 500 ) );			
      }
	}

	}
?>