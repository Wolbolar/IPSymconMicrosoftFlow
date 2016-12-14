<?

class FlowIO extends IPSModule
{

    public function Create()
    {
	//Never delete this line!
        parent::Create();
		
		//These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
		$this->RegisterPropertyString("username", "ipsymcon");
		$this->RegisterPropertyString("password", "user@h0me");		   
    }

    public function ApplyChanges()
    {
	//Never delete this line!
        parent::ApplyChanges();
        $change = false;
		
		$this->SetFlowInterface();
		$this->SetStatus(102);

	}	

		
################## Datapoints
 
	
		
			
	################## DATAPOINT RECEIVE FROM CHILD
	

	public function ForwardData($JSONString)
	{
	 
		// Empfangene Daten von der Splitter Instanz
		$data = json_decode($JSONString);
		
	 
		// Hier würde man den Buffer im Normalfall verarbeiten
		// z.B. CRC prüfen, in Einzelteile zerlegen
		try
		{
			// Absenden an Flow
		
			//IPS_LogMessage("Forward Data to Flow", utf8_decode($data->Buffer));
			
			//aufarbeiten
			$command = $data->Buffer;
			$result = $this->SendCommand ($command);
		}
		catch (Exception $ex)
		{
			echo $ex->getMessage();
			echo ' in '.$ex->getFile().' line: '.$ex->getLine().'.';
		}
	 
		return $result;
	}
		
	
	protected function SendJSON ($data)
	{
		// Weiterleitung zu allen Gerät-/Device-Instanzen
		$this->SendDataToChildren(json_encode(Array("DataID" => "{69D64BD2-528D-4204-95B1-DFE7485230F7}", "Buffer" => $data))); //Flow I/O RX GUI
	}
	
	protected function SendTriggerFlow($flowwebhook, $values)
	{
		$values_string = json_encode($values);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$flowwebhook);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_TIMEOUT, 5); //timeout after 5 seconds
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $values_string);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Content-Type: application/json',
		    'Content-Length: ' . strlen($values_string))
		);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
		$result=curl_exec ($ch);
		curl_close ($ch);
		return $result;
	}
	
	protected function SendCommand ($command)
	{
				
		//Semaphore setzen
        if ($this->lock("TriggerSend"))
        {
        // Daten senden
	        try
	        {
				$flowwebhook = $command->flowwebhook;
				$values = $command->values;
				//$system = "IPS4";
				IPS_LogMessage("Flow I/O:", "Trigger Flow request ".utf8_decode($flowwebhook));
				$result = $this->SendTriggerFlow($flowwebhook, $values);
				$data_string = json_encode($values);
				IPS_LogMessage("Flow I/O:", utf8_decode($data_string)." gesendet.");
	        }
	        catch (Exception $exc)
	        {
	            // Senden fehlgeschlagen
	            $this->unlock("TriggerSend");
	            throw new Exception($exc);
	        }
        $this->unlock("TriggerSend");
        }
        else
        {
			echo "Can not send to parent \n";
			$result = false;
			$this->unlock("TriggerSend");
			//throw new Exception("Can not send to parent",E_USER_NOTICE);
		  }
		
		return $result;
	
	}
	
	protected function SetFlowInterface()
		{
			$ipsversion = $this->GetIPSVersion();
			if($ipsversion == 0 || $ipsversion == 1)
				{
					//prüfen ob Script existent
					$SkriptID = @IPS_GetObjectIDByIdent("FlowIPSInterface", $this->InstanceID);
					if ($SkriptID === false)
						{
							$ID = $this->RegisterScript("FlowIPSInterface", "Flow IPS Interface", $this->CreateWebHookScript(), 4);
							IPS_SetHidden($ID, true);
							$this->RegisterHookOLD('/hook/flow', $ID);
						}
					else
						{
							//echo "Die Skript-ID lautet: ". $SkriptID;
						}
				}
			else
				{
					$SkriptID = @IPS_GetObjectIDByIdent("FlowIPSInterface", $this->InstanceID);
					if ($SkriptID > 0)
					{
						$this->UnregisterHook("/hook/flow");
						$this->UnregisterScript("FlowIPSInterface");
					}
					$this->RegisterHook("/hook/flow");
				}
		}
	
	private function RegisterHookOLD($WebHook, $TargetID)
		{
			$ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
			if (sizeof($ids) > 0)
			{
				$hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
				$found = false;
				foreach ($hooks as $index => $hook)
				{
					if ($hook['Hook'] == $WebHook)
					{
						if ($hook['TargetID'] == $TargetID)
							return;
						$hooks[$index]['TargetID'] = $TargetID;
						$found = true;
					}
				}
				if (!$found)
				{
					$hooks[] = Array("Hook" => $WebHook, "TargetID" => $TargetID);
				}
				IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
				IPS_ApplyChanges($ids[0]);
			}
		}
		
	private function CreateWebHookScript()
		{
        $Script = '<?
//Do not delete or modify.
FlowIO_ProcessHookDataOLD('.$this->InstanceID.');		
?>';	
		return $Script;
		}
		
	private function RegisterHook($WebHook)
		{
  			$ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
  			if(sizeof($ids) > 0)
				{
  				$hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
  				$found = false;
  				foreach($hooks as $index => $hook)
					{
					if($hook['Hook'] == $WebHook)
						{
						if($hook['TargetID'] == $this->InstanceID)
  							return;
						$hooks[$index]['TargetID'] = $this->InstanceID;
  						$found = true;
						}
					}
  				if(!$found)
					{
 					$hooks[] = Array("Hook" => $WebHook, "TargetID" => $this->InstanceID);
					}
  				IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
  				IPS_ApplyChanges($ids[0]);
				}
  		}
		
	/**
     * Löscht einen WebHook, wenn vorhanden.
     *
     * @access private
     * @param string $WebHook URI des WebHook.
     */
    protected function UnregisterHook($WebHook)
    {
        $ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
        if (sizeof($ids) > 0)
        {
            $hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
            $found = false;
            foreach ($hooks as $index => $hook)
            {
                if ($hook['Hook'] == $WebHook)
                {
                    $found = $index;
                    break;
                }
            }
            if ($found !== false)
            {
                array_splice($hooks, $index, 1);
                IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
                IPS_ApplyChanges($ids[0]);
            }
        }
    }  
	
	/**
     * Löscht eine Script, sofern vorhanden.
     *
     * @access private
     * @param int $Ident Ident der Variable.
     */
    protected function UnregisterScript($Ident)
    {
        $sid = @IPS_GetObjectIDByIdent($Ident, $this->InstanceID);
        if ($sid === false)
            return;
        if (!IPS_ScriptExists($sid))
            return; //bail out
        IPS_DeleteScript($sid, true);
    } 
	
	/**
		* This function will be available automatically after the module is imported with the module control.
		* Using the custom prefix this function will be callable from PHP and JSON-RPC 
		*
		*/
		public function ProcessHookDataOLD()
		{
			$username = $this->ReadPropertyString('username');
			$password = $this->ReadPropertyString('password');
			if(!isset($_SERVER['PHP_AUTH_USER']))
				$_SERVER['PHP_AUTH_USER'] = "";
			if(!isset($_SERVER['PHP_AUTH_PW']))
				$_SERVER['PHP_AUTH_PW'] = "";
			 
			if(($_SERVER['PHP_AUTH_USER'] != $username) || ($_SERVER['PHP_AUTH_PW'] != $password))
				{
				header('WWW-Authenticate: Basic Realm="Flow WebHook"');
				header('HTTP/1.0 401 Unauthorized');
				echo "Authorization required";
				return;
				}
			echo "Webhook Flow IP-Symcon 4";
			
			//workaround for bug
			if(!isset($_IPS))
				global $_IPS;
			if($_IPS['SENDER'] == "Execute")
				{
				echo "This script cannot be used this way.";
				return;
				} 

			 # Capture JSON content
			$flowjson = file_get_contents('php://input');
			$data = json_decode($flowjson);
			$this->SendJSON($data);
			IPS_LogMessage("Flow I/O:", utf8_decode($flowjson)." empfangen.");	
		}
	
	/**
 	* This function will be called by the hook control. Visibility should be protected!
  	*/
		
	protected function ProcessHookData()
	{
		$username = $this->ReadPropertyString('username');
		$password = $this->ReadPropertyString('password');
		if(!isset($_SERVER['PHP_AUTH_USER']))
			$_SERVER['PHP_AUTH_USER'] = "";
		if(!isset($_SERVER['PHP_AUTH_PW']))
			$_SERVER['PHP_AUTH_PW'] = "";
			 
		if(($_SERVER['PHP_AUTH_USER'] != $username) || ($_SERVER['PHP_AUTH_PW'] != $password))
			{
			header('WWW-Authenticate: Basic Realm="Flow WebHook"');
			header('HTTP/1.0 401 Unauthorized');
			echo "Authorization required";
			return;
			}
		echo "Webhook Flow IP-Symcon 4";
			
		//workaround for bug
		if(!isset($_IPS))
			global $_IPS;
		if($_IPS['SENDER'] == "Execute")
			{
			echo "This script cannot be used this way.";
			return;
			} 

		 # Capture JSON content
		$flowjson = file_get_contents('php://input');
		$data = json_decode($flowjson);
		$this->SendJSON($data);
		IPS_LogMessage("Flow I/O:", utf8_decode($flowjson)." empfangen.");	
	}
	
	################## SEMAPHOREN Helper  - private

    private function lock($ident)
    {
        for ($i = 0; $i < 3000; $i++)
        {
            if (IPS_SemaphoreEnter("Flow_" . (string) $this->InstanceID . (string) $ident, 1))
            {
                return true;
            }
            else
            {
                IPS_Sleep(mt_rand(1, 5));
            }
        }
        return false;
    }

    private function unlock($ident)
    {
          IPS_SemaphoreLeave("Flow_" . (string) $this->InstanceID . (string) $ident);
    }
	
	protected function GetIPSVersion ()
		{
			$ipsversion = IPS_GetKernelVersion ( );
			$ipsversion = explode( ".", $ipsversion);
			$ipsmajor = intval($ipsversion[0]);
			$ipsminor = intval($ipsversion[1]);
			if($ipsminor < 10) // 4.0
			{
				$ipsversion = 0;
			}
			elseif ($ipsminor >= 10 && $ipsminor < 20) // 4.1
			{
				$ipsversion = 1;
			}
			else   // 4.2
			{
				$ipsversion = 2;
			}
			return $ipsversion;
		}
}

?>