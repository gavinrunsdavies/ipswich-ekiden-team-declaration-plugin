<?php
namespace IpswichEkidenTeamDeclaration\V1;
	
require_once plugin_dir_path( __FILE__ ) .'class-ipswich-ekiden-team-declaration-data-access-v1.php';

class Ipswich_Ekiden_Team_Declaration_API_Controller_V1 {
	
	private $data_access;
	
	private $user;
  
  const Unattached = 989;
  const Male = "Male";
  const Female = "Female";
	
	public function __construct() {
		$this->data_access = new Ipswich_Ekiden_Team_Declaration_Data_Access();
	}
	
	public function rest_api_init( ) {			
		
		$namespace = 'ipswich-ekiden-team-declaration-api/v1'; // base endpoint for our custom API
				
		$this->register_routes_authentication($namespace);
		$this->register_routes_teams($namespace);						
    $this->register_routes_contact($namespace);		    	
		
		add_filter( 'rest_endpoints', array( $this, 'remove_wordpress_core_endpoints'), 10, 1 );		

    // Customise new user email
    add_filter( 'wp_new_user_notification_email', array($this, 'custom_wp_new_user_notification_email'), 10, 3 );
    
	}
	
	public function plugins_loaded() {

		// enqueue WP_API_Settings script
		add_action( 'wp_print_scripts', function() {
			wp_enqueue_script( 'wp-api' );
		} );					
	}
	
	private function register_routes_authentication($namespace) {
    register_rest_route( $namespace, '/users', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'create_user' ),
			'args'                => array(
				'email'           => array(
					'required'          => true
					),
        'password'           => array(
					'required'          => true
					),
        'firstName'           => array(
					'required'          => true
					),
        'lastName'           => array(
					'required'          => true
					)
				)			
		) ); 
	}
  
  private function register_routes_contact($namespace) {
    register_rest_route( $namespace, '/message', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'send_message' ),
			'args'                => array(
				'email'           => array(
					'required'          => true
					),
        'message'           => array(
					'required'          => true
					),
        'firstName'           => array(
					'required'          => true
					),
        'lastName'           => array(
					'required'          => true
					)
				)			
		) ); 
  }

  private function register_routes_teams($namespace) {		

     register_rest_route( $namespace, '/statistics/', array(
			'methods'             => \WP_REST_Server::READABLE,				
			'callback'            => array( $this, 'get_statistics' )
		) );
  
    register_rest_route( $namespace, '/clubs/', array(
			'methods'             => \WP_REST_Server::READABLE,				
			'callback'            => array( $this, 'get_clubs' )
		) );	   

    register_rest_route( $namespace, '/teams', array(
			'methods'             => \WP_REST_Server::READABLE,				
			'callback'            => array( $this, 'get_teams' )
    ) );	
    
    register_rest_route( $namespace, '/teams/download', array(
      'methods'             => \WP_REST_Server::READABLE,				
      'permission_callback' => array( $this, 'permission_editor_check' ),
			'callback'            => array( $this, 'download_teams' )
    ) );
    
    register_rest_route( $namespace, '/teams/send', array(
      'methods'             => \WP_REST_Server::CREATABLE,				
      'permission_callback' => array( $this, 'permission_editor_check' ),
      'callback'            => array( $this, 'send_teams' ),
      'args'                => array(
				'email'             => array(
					'required'        => true
					)
				)
		) );

		register_rest_route( $namespace, '/teams/(?P<id>[\d]+)', array(
			'methods'             => \WP_REST_Server::READABLE,				
			'callback'            => array( $this, 'get_team' ),
			'args'                => array(
				'id'           => array(
					'required'          => true,						
					'validate_callback' => array( $this, 'is_valid_id' )
					)
				)
		) );    

    // Get teams for logged in user
    register_rest_route( $namespace, '/myteams', array(
			'methods'             => \WP_REST_Server::READABLE,				
      'permission_callback' => array( $this, 'permission_check' ),
			'callback'            => array( $this, 'get_my_teams' )
		) );

    // Must be authenticated
		register_rest_route( $namespace, '/teams', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'permission_callback' => array( $this, 'permission_check' ),
			'callback'            => array( $this, 'create_team' ),
			'args'                => array(
				'name'           => array(
					'required'          => true
					),
				'clubId'           => array(
					'required'          => true,
          'validate_callback' => array( $this, 'is_valid_id' )
					)
				)      
		) );    
    
    // PUT - updates
		register_rest_route( $namespace, '/teams/(?P<id>[\d]+)', array(
			'methods'             => \WP_REST_Server::EDITABLE,
			'permission_callback' => array( $this, 'permission_check' ),
			'callback'            => array( $this, 'update_team' ),
			'args'                => array(
				'id'                => array(
					'required'          => true,						
					'validate_callback' => array( $this, 'is_valid_id' )
					),
				'name'           => array(
					'required'          => true
					),
				'clubId'           => array(
					'required'          => true,
          'validate_callback' => array( $this, 'is_valid_id' )
					)
				)
    ));
    
    register_rest_route( $namespace, '/teams/(?P<id>[\d]+)/runners/(?P<leg>[1-6])', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'permission_callback' => array( $this, 'permission_check' ),
			'callback'            => array( $this, 'add_team_runner' ),
			'args'                => array(
				'id'                => array(
					'required'          => true,						
					'validate_callback' => array( $this, 'is_valid_id' )
					),
        'leg'                => array(
					'required'          => true,						
					'validate_callback' => array( $this, 'is_valid_runner_leg' )
					)
				)
    ));
    
     // Patch - updates
		register_rest_route( $namespace, '/teams/(?P<id>[\d]+)/runners/(?P<leg>[1-6])', array(
			'methods'             => \WP_REST_Server::EDITABLE,
			'permission_callback' => array( $this, 'permission_check' ),
			'callback'            => array( $this, 'update_team_runner' ),
			'args'                => array(
				'id'                => array(
					'required'          => true,						
					'validate_callback' => array( $this, 'is_valid_id' )
					),
        'leg'                => array(
					'required'          => true,						
					'validate_callback' => array( $this, 'is_valid_runner_leg' )
					),
				'field'           => array(
					'required'          => true,
					'validate_callback' => array( $this, 'is_valid_team_runner_update_field' )
					),
				'value'           => array(
					'required'          => true
					)
				)
    ));
    
    register_rest_route( $namespace, '/teams/(?P<id>[\d]+)', array(
			'methods'             => \WP_REST_Server::DELETABLE,
			'callback'            => array( $this, 'delete_team' ),
			'permission_callback' => array( $this, 'permission_check' ),
			'args'                => array(
				'id'           => array(
					'required'          => true,						
					'validate_callback' => array( $this, 'is_valid_id' )
					)
				)
		) );
	}		
		
		public function permission_check( \WP_REST_Request $request ) {
      $current_user = wp_get_current_user();
      
      if (!($current_user instanceof \WP_User) || $current_user->ID == 0) {
        return new \WP_Error( 'rest_forbidden',
					sprintf( 'You do not have enough privileges to use this API.' ), array( 'status' => 403, 'User' => $current_user->ID ) );
      }
      
      return true;
    }
    
    public function permission_editor_check( \WP_REST_Request $request ) {
      $current_user = wp_get_current_user();
      
      if (!(current_user_can('editor') || current_user_can('administrator'))) {
        return new \WP_Error( 'rest_forbidden',
					sprintf( 'You do not have enough privileges to use this API.' ), array( 'status' => 403, 'User' => $current_user->ID ) );
      }
      
      return true;
		}
	
		/**
		 * Unsets all core WP endpoints registered by the WordPress REST API (via rest_endpoints filter)
		 * @param  array   $endpoints   registered endpoints
		 * @return array
		 */
		public function remove_wordpress_core_endpoints( $endpoints ) {

			foreach ( array_keys( $endpoints ) as $endpoint ) {
				if ( stripos( $endpoint, '/wp/v2' ) === 0 ) {
					unset( $endpoints[ $endpoint ] );
				}
			}

			return $endpoints;
    }
    

    public function send_teams(\WP_REST_Request $request) {
      
      $uid = md5(uniqid(time()));
      $user = wp_get_current_user();
      $fromAddress = $user->first_name . " " . $user->last_name . " <" . $user->user_email .">";
                  
      // Additional headers     
      $headers = array();
      $headers[] = 'From: ' . $fromAddress;    	
      $headers[]= 'Cc: admin@ipswichekiden.co.uk';
      //$headers .= 'MIME-Version: 1.0' . "\r\n";
      //$headers .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
      $headers[] = 'Content-Type: text/html; charset=UTF-8';
          
      $subject = "Ipswich Ekiden Team Declaration submitted teams ";
         
      $footerHtml  = "<br><br><p><small>This email was automatically sent via a request made on the Ipswich Ekiden Team declaration Portal.</small></p>";
      
      $html = '<p>Please find attached the declared and teams for the Ipswich Ekiden</p>';     
      $html .= $footerHtml;

      $filename = "IpswichEkidenLTeam".date("Ymd").".csv";
      $data = $this->data_access->get_data();      

      $jsonDecoded = json_decode(json_encode($data), true);

      //Open file pointer.
      $fp = fopen($filename, 'w');

      // Add headers
      fputcsv($fp, array('Ekiden Runners','','Individual Runner','','Leg','','','','Indivdiual Runner','Indivdiual Runner','Male or','','Ekiden Race number format'));
      fputcsv($fp, array('Team Number','Colour','& Chip Number','Suffix','Number','Category','Team name','team name 12 characters (display purposes)','First name','Second name','Female','Age','Team Number'));          

      foreach($jsonDecoded as $row){          
          fputcsv($fp, $row);
      }

      fclose($fp);

      $attachment = array(realpath($filename));   

      wp_mail($request['email'], $subject, $html, $headers, $attachment);      
      
      return rest_ensure_response(null);
    }

    public function download_teams(\WP_REST_Request $request) {
      $teams = $this->data_access->get_data();  

      return rest_ensure_response($teams);
    }
    
    public function send_message(\WP_REST_Request $request) {
      // To send HTML mail, the Content-type header must be set
      $headers  = 'MIME-Version: 1.0' . "\r\n";
      $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

      $fromName = $request['firstName'] . " " . $request['lastName'];
      $fromAddress =  $fromName. " <" . $request['email']  .">";
      
      // Additional headers     
      $headers .= 'From: ' . $fromAddress . "\r\n";
    	$headers .= 'Cc: '. $request['email'] . "\r\n";
      $headers .= 'Cc: admin@ipswichekiden.co.uk' . "\r\n";
 
      $subject = "Ipswich Ekiden Team Declaration inquiry from ". $fromName;
         
      $footerHtml  = "<br><br><p><small>This email was automatically sent via a request made on the Ipswich Ekiden Team declaration Portal.</small></p>";
      
      $html = '<p>Contact inquiry from Ipswich Ekiden Team declaration Portal.</p>';
      $html .= sprintf('<p>From %s; email: %s</p>', $fromName, $request['email']); 
      $html .= '<p>Message:</p>';
      $html .= '<blockquote>';
      $html .= $request['message'];
      $html .= '</blockquote>';
      $html .= $footerHtml;

      mail('info@ipswichekiden.co.uk', $subject, $html, $headers);
      
      return rest_ensure_response(null);
    }
    
    public function custom_wp_new_user_notification_email( $wp_new_user_notification_email, $user, $blogname ) {
        $subject = sprintf("Ipswich Ekiden Team Declaration Registration - %s", $user->user_login);
        $message = sprintf("Hi %s,\r\n\r\n", $user->display_name);
        $message .= "Welcome to the Ipswich Ekiden Team Declaration Portal. To access the portal visit www.ipswichekiden.co.uk/app and use this email as login username and the password you choose at registration.\r\n\r\n";        
        $message .= "Any questions please contact support.";
        $wp_new_user_notification_email['headers'] = "From: Ipswich Ekiden <admin@ipswichekiden.co.uk>";
        $wp_new_user_notification_email['subject'] = $subject;
        $wp_new_user_notification_email['message'] = $message;
        return $wp_new_user_notification_email;
    }
    
    public function create_user(\WP_REST_Request $request) {      
      $displayName = sprintf('%s %s', $request['firstName'], $request['lastName']);
      $user_id = wp_insert_user( array( 
        'user_login'  =>  $request['email'],
        'user_email'  =>  $request['email'],
        'user_pass'   => $request['password'],
        'display_name' => $displayName,
        'first_name' => $request['firstName'],
        'last_name' => $request['lastName'],
        'role' => 'EkidenTeamDeclaratioon'
      ) );

      if ( is_wp_error( $user_id ) ) {
         return new \WP_Error( 'rest_invalid_param',
					sprintf( 'Registration update failed for username %s', $request['email'] ), array( 'status' => 400 ) );
      }  
      
      // Inform user and admin of new registration
      wp_new_user_notification($user_id, null, "both");
      
      $response = new \stdClass;
			$response->display_name = $displayName;
		  $response->email = $request['email'];
      $response->firstName = $request['firstName'];
      $response->lastName = $request['lastName'];
      $response->password = $request['password'];

      return rest_ensure_response( $response );      
    }
    
     public function get_statistics(\WP_REST_Request $request) {
      $clubTeamsCount = $this->data_access->get_club_team_count();
      
      $runnerCategoryCount = $this->data_access->get_runner_category_count();
      
      $teamsResponse = $this->data_access->get_teams(null);      
      
      $teams = $this->add_runners_to_teams($teamsResponse);
      
      foreach ($teams as &$team) {
        $this->update_team_category($team);
      }
                  
      $response = new \stdClass;
			$response->clubTeamsCount = $clubTeamsCount;
		  $response->runnerCategoryCount = $runnerCategoryCount;
      $response->teamCategoryCount = array();
      $response->totalTeamsCount = count($teams);
      $response->completeTeamsCount = 0;
      $response->seniorTeamsCount = 0;
      $response->juniorTeamsCount = 0;
      $response->maleRunnerCount = 0;
      $response->femaleRunnerCount = 0;      
      
      $teamCategoryCount = array();
      
      for ($i = 0; $i < count($teams); $i++) {                
        if ($teams[$i]->complete) {
          $response->completeTeamsCount++;         
          
          if (array_key_exists($teams[$i]->category, $teamCategoryCount)) {
            $teamCategoryCount[$teams[$i]->category] += 1;
          } else {
            $teamCategoryCount[$teams[$i]->category] = 1;
          }
        }
        
        if ($teams[$i]->isJuniorTeam) {
          $response->juniorTeamsCount++;
        } else {
          $response->seniorTeamsCount++;
        }
        
        for ($j = 0; $j < count($teams[$i]->runners); $j++) {                        
          if ($teams[$i]->runners[$j]->gender == self::Male) {
            $response->maleRunnerCount++;
          } else if ($teams[$i]->runners[$j]->gender == self::Female) {
            $response->femaleRunnerCount++;
          }
        }                
      }
      
      $teamCategoryCount['Uncategorized'] = count($teams) - $response->completeTeamsCount;
      
      foreach($teamCategoryCount as $category => $count) {
        $statisticItem = new \stdClass;
        $statisticItem->name = $category;
        $statisticItem->value = $count;
        
        $response->teamCategoryCount[] = $statisticItem;
      }
      		
      return rest_ensure_response( $response );
    }
       
    public function get_clubs(\WP_REST_Request $request) {
      $response = $this->data_access->get_clubs();
		
      return rest_ensure_response( $response );
    }
    
    public function get_teams(\WP_REST_Request $request) {
      
      $parameters = $request->get_query_params();
      
      if (isset($parameters['race'])) {
        if ($parameters['race'] == "seniors") {
        $response = $this->data_access->get_teams(0);    
      } else {      
        $response = $this->data_access->get_teams(1);      
      }
      } else {
        $response = $this->data_access->get_teams(null);  
      }
            
      $teams = $this->add_runners_to_teams($response);
      
      foreach ($teams as &$team) {
        $this->update_team_category($team);
      }
		
      return rest_ensure_response( $teams );
    }
		
    public function get_team(\WP_REST_Request $request) {
      $response = $this->data_access->get_team($request['id']);
		
      $this->update_team_category($response);
      
      return rest_ensure_response( $response );
    }
    
    public function get_my_teams(\WP_REST_Request $request) {
      $current_user = wp_get_current_user();
      
      if (!($current_user instanceof \WP_User) || $current_user->ID == 0) {
        return new \WP_Error( 'rest_forbidden',
					sprintf( 'You do not have enough privileges to use this API.' ), array( 'status' => 403 ) );
      }
      
      if( current_user_can('editor') || current_user_can('administrator') ) {
        $response = $this->data_access->get_all_teams();  
      } else {      
        $response = $this->data_access->get_my_teams($current_user->ID);         
      }

      $teams = $this->add_runners_to_teams($response);
      
      foreach ($teams as &$team) {
        $this->update_team_category($team);
      }
		
      return rest_ensure_response( $teams );      		
    }    
    
    public function create_team(\WP_REST_Request $request) {
      $current_user = wp_get_current_user();
      
      if (!($current_user instanceof \WP_User) || $current_user->ID == 0) {
        return new \WP_Error( 'rest_forbidden',
					sprintf( 'You do not have enough privileges to use this API.' ), array( 'status' => 403, 'User' => $current_user->ID ) );
      }     
      
      $response = $this->data_access->create_team($current_user->ID, $request['name'], $request['clubId'], $request['isJuniorTeam']);      
		
      return rest_ensure_response( $response );
    }     

    public function update_team(\WP_REST_Request $request) {   
      if (!$this->is_valid_team_captain($request['id'])) {
        return new \WP_Error( 'rest_forbidden',
					sprintf( 'You do not have enough privileges to use this update this team.' ), array( 'status' => 403 ) );
      }
        
      $response = $this->data_access->update_team($request['id'], $request['name'], $request['clubId'], $request['isJuniorTeam']);           
      
      foreach ($request['runners'] as $runner) {
        if ( !empty($runner['name']) || !empty($runner['ageCategory']) || !empty($runner['gender'])) {          
          $response = $this->data_access->update_team_runner($request['id'], $runner['leg'], $runner['name'], $runner['gender'], $runner['ageCategory']);
        }
      }
      
      $response = $this->data_access->get_team($request['id']);
		
      $this->update_team_category($response);
     
      return rest_ensure_response( $response );
    } 

    public function delete_team(\WP_REST_Request $request) {
      if (!$this->is_valid_team_captain($request['id'])) {
        return new \WP_Error( 'rest_forbidden',
					sprintf( 'You do not have enough privileges to use this delete this team.' ), array( 'status' => 403 ) );
      }
      
      $response = $this->data_access->delete_team($request['id']);
		
      return rest_ensure_response( $response );
    }   
    
     public function add_team_runner(\WP_REST_Request $request) {
      if (!$this->is_valid_team_captain($request['id'])) {
        return new \WP_Error( 'rest_forbidden',
					sprintf( 'You do not have enough privileges to use this update this team.' ), array( 'status' => 403 ) );
      }
      
      $response = $this->data_access->add_team_runner($request['id'], $request['leg'], $request['name'], $request['gender'], $request['ageCategory']);
            	
      return rest_ensure_response( $response );
    } 

    public function update_team_runner(\WP_REST_Request $request) {     
      if (!$this->is_valid_team_captain($request['id'])) {
        return new \WP_Error( 'rest_forbidden',
					sprintf( 'You do not have enough privileges to use this update this team.' ), array( 'status' => 403 ) );
      } 
      
      $response = $this->data_access->update_team_runner($request['id'], $request['leg'], $request['field'], $request['value']);
		
      return rest_ensure_response( $response );
    }     
    
    private function is_valid_team_captain ($teamId) {
      $current_user = wp_get_current_user();
      
      if (!($current_user instanceof \WP_User) || $current_user->ID == 0)
        return false;
      
      $response = $this->data_access->get_team($teamId);
      return $response->captainId == $current_user->ID;      
    }

	public function is_valid_id( $value, $request, $key ) {
		if ( $value < 1 ) {
			// can return false or a custom \WP_Error
			return new \WP_Error( 'rest_invalid_param',
				sprintf( '%s %d must be greater than 0', $key, $value ), array( 'status' => 400 ) );
		} else {
			return true;
		}
	}		
  
  	public function is_valid_runner_leg( $value, $request, $key ) {
		if (!in_array($value, array(1,2,3,4,5,6))) {
			return new \WP_Error( 'rest_invalid_param',
				sprintf( '%s %d must between 1 and 6', $key, $value ), array( 'status' => 400 ) );
		} else {
			return true;
		}
	}
  
  public function is_valid_team_update_field($value, $request, $key){
			if ( $value == 'name' || $value == 'clubId' ) {
				return true;
			} else {
				return new \WP_Error( 'rest_invalid_param',
					sprintf( '%s %d must be name or clubId only.', $key, $value ), array( 'status' => 400 ) );
			} 			
		}
    
      public function is_valid_team_runner_update_field($value, $request, $key){
			if ( $value == 'name' || $value == 'gender' || $value == 'ageCategory' ) {
				return true;
			} else {
				return new \WP_Error( 'rest_invalid_param',
					sprintf( '%s %d must be name, gender or ageCategory only.', $key, $value ), array( 'status' => 400 ) );
			} 			
		}
    
    public function is_valid_new_user($model, $request, $key) {
      if ( empty($model['email'])) {				
				return new \WP_Error( 'rest_invalid_param',
					sprintf( '%s has invalid email value.', $key), array( 'status' => 400 ) );
			} 
      
      if ( empty($model['password'])) {				
				return new \WP_Error( 'rest_invalid_param',
					sprintf( '%s has invalid password value.', $key), array( 'status' => 400 ) );
			} 
      
      if ( empty($model['firstName'])) {				
				return new \WP_Error( 'rest_invalid_param',
					sprintf( '%s has invalid firstName value.', $key), array( 'status' => 400 ) );
			} 
      
      if ( empty($model['lastName'])) {				
				return new \WP_Error( 'rest_invalid_param',
					sprintf( '%s has invalid lastName value.', $key), array( 'status' => 400 ) );
			}

      return true;      
    }
    
    private function get_junior_team_category($team) {
      if (count($team->runners) != 4) {
        return null; 
      }
      
      $teamCategory;
      $youngestMale = "U11";
      $youngestFemale = "U11";
      $allMale = true;
      $allFemale = true;
      
      for ($i = 0; $i < count($team->runners); $i++) {        
        if ( empty($team->runners[$i]->name) || 
              empty($team->runners[$i]->ageCategory) ||
              empty($team->runners[$i]->gender)) {
            return null;
        }
        
        if ($team->runners[$i]->gender == self::Male) {
          $allFemale = false;
          
          if ($team->runners[$i]->ageCategory > $youngestMale) {
            $youngestMale = $team->runners[$i]->ageCategory;
          }
        } elseif ($team->runners[$i]->gender == self::Female) {
          $allMale = false;
          
          if ($team->runners[$i]->ageCategory > $youngestFemale) {
            $youngestFemale = $team->runners[$i]->ageCategory;
          }
        }
      }
      
      if ($allMale && $youngestMale == "U11") {
        $teamCategory = "U11B";
      } elseif ($allMale) {
        $teamCategory = "12B";
      } elseif ($allFemale && $youngestFemale == "U11") {
        $teamCategory = "U11G";
      } elseif ($allFemale) {
        $teamCategory = "12G";
      } elseif ($youngestFemale == "U11" && $youngestMale == "U11") {
        $teamCategory = "U11MX";
      } else {
        $teamCategory = "12MX"; // Default
      }
      
      return $teamCategory;
    }
    
    private function get_senior_team_category($team) {
      if (count($team->runners) != 6) {
        return null; 
      }
      
      $teamCategory;
      $youngestMale = "V70";
      $youngestFemale = "V70";
      $allMale = true;
      $allFemale = true;
      $numberOfFemale = 0;      
      
      for ($i = 0; $i < count($team->runners); $i++) {        
        if ( empty($team->runners[$i]->name) || 
              empty($team->runners[$i]->ageCategory) ||
              empty($team->runners[$i]->gender)) {
            return null;
        }
       
        if ($team->clubId == self::Unattached) {
          return 'Unaffiliated / Social';          
        }
        
        if ($team->runners[$i]->gender == self::Male) {
          $allFemale = false;
          
          if ($team->runners[$i]->ageCategory < $youngestMale) {
            $youngestMale = $team->runners[$i]->ageCategory;
          }
        } elseif ($team->runners[$i]->gender == self::Female) {
          $allMale = false;
          $numberOfFemale++;
          
          if ($team->runners[$i]->ageCategory < $youngestFemale) {
            $youngestFemale = $team->runners[$i]->ageCategory;
          }
        }        
      }
      
      if ($allMale && $youngestMale == "Open") {
        $teamCategory = "MensOpen";
      } elseif ($allMale && $youngestMale == "V40") {
        $teamCategory = "MensVet";
      } elseif ($allMale && $youngestMale == "V50") {
        $teamCategory = "MensSuperVet";
      } elseif ($allFemale && $youngestFemale == "Open") {
        $teamCategory = "LadiesOpen";
      } elseif ($allFemale && $youngestFemale == "V35") {
        $teamCategory = "LadiesVet";
      } elseif ($allFemale && $youngestFemale == "V45") {
        $teamCategory = "LadiesSuperVet";
      } elseif (($youngestMale == "V60" && $allMale) || ($youngestFemale == "V60" && $allFemale) || ($youngestMale == "V60" && $youngestFemale == "V60")) {
        $teamCategory = "Over60";
      } elseif (($youngestMale == "V70" && $allMale) || ($youngestFemale == "V70" && $allFemale) || ($youngestMale >= "V70" && $youngestFemale >= "V70")) {
        $teamCategory = "Over70";
      } elseif ($numberOfFemale >= 2 && $allFemale == false) {
        $teamCategory = "Mixed";
      } else {
        // Default
        $teamCategory = "MensOpen";
      }
      
      return $teamCategory;
    }    
    
    private function update_team_category($team) {
      if ($team->isJuniorTeam) {
        $teamCategory = $this->get_junior_team_category($team);
      } else {
        $teamCategory = $this->get_senior_team_category($team);
      }
      $team->complete = ($teamCategory != null);
      $team->category = $teamCategory;      
    }
    
    private function add_runners_to_teams($results) {
      foreach ($results->teams as &$team) {
        $team->runners = array();
        
        foreach ($results->runners as $k => $runner) {
          if ($runner->teamId == $team->id) {
            unset($runner->teamId);
            array_push($team->runners, $runner);
            unset($k);
          }
        }
      }
      
      return $results->teams;
    }
}
?>