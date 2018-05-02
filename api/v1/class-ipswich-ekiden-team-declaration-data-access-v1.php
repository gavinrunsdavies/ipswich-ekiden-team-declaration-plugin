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
  
    public function get_teams($isJuniorTeam) {  	
      
      if (is_null($isJuniorTeam)) {
        $sql = "SELECT t.id as id, t.name as name, c.id as clubId, c.name as clubName, t.is_junior_team as isJuniorTeam, t.number as number
              FROM ietd_teams t
              INNER JOIN ietd_clubs c on c.id = t.club_id
              ORDER BY c.name, t.name";
      } else {
        $sql = $this->db->prepare("SELECT t.id as id, t.name as name, c.id as clubId, c.name as clubName, t.is_junior_team as isJuniorTeam, t.number as number
              FROM ietd_teams t              
              INNER JOIN ietd_clubs c on c.id = t.club_id
              WHERE t.is_junior_team = %d
              ORDER BY c.name, t.name", $isJuniorTeam);
      }
							
			$teams = $this->db->get_results($sql, OBJECT);
      
      if ($this->db->num_rows == 0)
				$teams = array();
      
      $sql = "SELECT tr.team_id as teamId, r.id as runnerId, r.name as name, r.gender as gender, r.age_category as ageCategory, tr.leg as leg
            FROM ietd_team_runners tr
            INNER JOIN ietd_runners r ON r.id = tr.runner_id
            ORDER BY teamId, leg";
            
      $runners = $this->db->get_results($sql, OBJECT);
      
      if ($this->db->num_rows == 0)
				$runners = array();

      $results = new \stdclass;
      $results->teams = $teams;
      $results->runners = $runners;

			return $results;
	}
	
	    public function get_my_teams($captainId) {

          $sql = $this->db->prepare("SELECT t.id as id, t.name as name, c.id as clubId, c.name as clubName, t.is_junior_team as isJuniorTeam, t.number as number
              FROM ietd_teams t
              INNER JOIN ietd_clubs c on c.id = t.club_id
              WHERE t.captain_id = %d
              ORDER BY t.name", $captainId);        
							
			$teams = $this->db->get_results($sql, OBJECT);
      
      if ($this->db->num_rows == 0)
				return array();
      
      $sql = $this->db->prepare("SELECT tr.team_id as teamId, r.id as runnerId, r.name as name, r.gender as gender, r.age_category as ageCategory, tr.leg as leg
            FROM ietd_team_runners tr
            INNER JOIN ietd_runners r ON r.id = tr.runner_id
            INNER JOIN ietd_teams t ON t.id = tr.team_id
            WHERE t.captain_id = %d 
            ORDER BY teamId, leg", $captainId);
            
      $runners = $this->db->get_results($sql, OBJECT);
      
      if ($this->db->num_rows == 0)
				$runners = array();

      $results = new \stdclass;
      $results->teams = $teams;
      $results->runners = $runners;

			return $results;    
  }
  
  public function get_all_teams() {
  
      $sql = "SELECT t.id as id, t.name as name, c.id as clubId, c.name as clubName, t.is_junior_team as isJuniorTeam, t.number as number
          FROM ietd_teams t
          INNER JOIN ietd_clubs c on c.id = t.club_id
          ORDER BY t.name";  
          
  $teams = $this->db->get_results($sql, OBJECT);
  
  if ($this->db->num_rows == 0)
    return array();
  
  $sql = "SELECT tr.team_id as teamId, r.id as runnerId, r.name as name, r.gender as gender, r.age_category as ageCategory, tr.leg as leg
        FROM ietd_team_runners tr
        INNER JOIN ietd_runners r ON r.id = tr.runner_id
        INNER JOIN ietd_teams t ON t.id = tr.team_id
        ORDER BY teamId, leg";
        
  $runners = $this->db->get_results($sql, OBJECT);
  
  if ($this->db->num_rows == 0)
    $runners = array();

  $results = new \stdclass;
  $results->teams = $teams;
  $results->runners = $runners;

  return $results;    
}
  
      public function get_team($teamId) {  	
       $sql = $this->db->prepare("SELECT t.id as id, t.name as name, c.id as clubId, c.name as clubName, t.captain_id as captainId, t.is_junior_team as isJuniorTeam, t.number as number
              FROM ietd_teams t
              INNER JOIN ietd_clubs c on c.id = t.club_id
			  WHERE t.id = %d
        ", $teamId);
		  
		  $team = $this->db->get_row($sql);
		  
      $sql = $this->db->prepare("SELECT r.id as id, r.name as name, r.gender as gender, r.age_category as ageCategory, tr.leg as leg
            FROM ietd_team_runners tr
            INNER JOIN ietd_runners r ON r.id = tr.runner_id
            WHERE tr.team_id = %d
            ORDER BY leg ASC", $teamId);
							
			$runners = $this->db->get_results($sql, OBJECT);          
      
      $team->runners = $runners;
      
			return $team;
	}
  
      public function create_team($teamCaptainId, $name, $clubId, $isJuniorTeam = 0) {  	
      
      $sql = $this->db->prepare("INSERT INTO ietd_teams(name, club_id, captain_id, is_junior_team) VALUES (%s, %d, %d, %d)", $name, $clubId, $teamCaptainId, $isJuniorTeam);
							
			$result = $this->db->query($sql, OBJECT);
			
      if ($result)	{	
        return $this->get_team($this->db->insert_id);
      }
      	
			return new \WP_Error( 'create_team',
						'Unknown error in reading results from the database', array( 'status' => 500, 'sql' => $sql ) );			
	}
  
   public function update_team($id, $name, $clubId, $isJuniorTeam = 0) {  	     
    $sql = $this->db->prepare("UPDATE ietd_teams SET name = '%s', club_id = %d, is_junior_team = %d WHERE id = %d", $name, $clubId, $isJuniorTeam, $id);    
    
    $result = $this->db->query($sql, OBJECT);
        
		return $result;
  }
  
  public function update_team_number($teamId, $number) {
    $sql = $this->db->prepare("UPDATE ietd_teams SET number = %d WHERE id = %d", $number, $teamId);    
    
    $result = $this->db->query($sql, OBJECT);
        
		return $result;
  }
  
        public function add_team_runner($teamId, $leg, $name, $gender, $ageCategory) {  	
      
      $sql = $this->db->prepare("INSERT INTO ietd_runners(name, age_category, gender) VALUES (%s, %s, %s)", $name, $ageCategory, $gender);
							
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
  
     public function update_team_runner($teamId, $leg, $name, $gender, $ageCategory) {  	
     
      $sql = $this->db->prepare("SELECT r.id as id
      FROM ietd_team_runners tr
      INNER JOIN ietd_runners r ON r.id = tr.runner_id
      WHERE tr.team_id = %d AND tr.leg = %d", $teamId, $leg);
      
      $runnerId = $this->db->get_var($sql);
      
      if ($runnerId > 0) {                            
        $sql = $this->db->prepare("UPDATE ietd_runners r
                                   SET r.name = '%s', r.age_category = '%s', r.gender = '%s'  
                                   WHERE r.id = %d", $name, $ageCategory, $gender, $runnerId);

        $result = $this->db->query($sql, OBJECT);
        
        if (!$result) {
            return new \WP_Error( 'update_team_runner',
              'Unknown error in updating team in to the database', array( 'status' => 500 ) );
        }     
      } else {
        return $this->add_team_runner($teamId, $leg, $name, $gender, $ageCategory);
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
      
    public function get_club_team_count() {  	
            
			$sql = "SELECT c.name as name, COUNT(t.Id) as value FROM ietd_teams t INNER JOIN ietd_clubs c on c.id = t.club_id GROUP by c.id ORDER BY c.name";

			$result = $this->db->get_results($sql, OBJECT);
      	
      if (!$result) {	
			return new \WP_Error( 'get_club_team_count',
						'Unknown error in getting club team statistics from the database', array( 'status' => 500 ) );			
      }
		
		return $result;
	}
  
  public function get_runner_category_count() {  	
            
			$sql = "SELECT age_category as name, COUNT(id) as value FROM ietd_runners GROUP by age_category ORDER BY age_category";

			$result = $this->db->get_results($sql, OBJECT);
      	
      if (!$result) {	
			return new \WP_Error( 'get_runner_category_count',
						'Unknown error in getting club runner statistics from the database', array( 'status' => 500 ) );			
      }
	  
	  return $result;
	}
}
?>