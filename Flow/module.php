<?

	class Flow extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			$this->ConnectParent("{2AC67CFD-5116-40BD-A7C8-456F9A9B16D2}", "Flow Splitter"); //Flow Splitter
			$this->RegisterPropertyString("flowwebhook", "");
			$this->RegisterPropertyInteger("selection", 0);
			$this->RegisterPropertyInteger("countsendvars", 0);
			$this->RegisterPropertyInteger("countrequestvars", 0);
			$this->RegisterPropertyBoolean("flowreturn", false);
			for ($i=1; $i<=15; $i++)
			{
				$this->RegisterPropertyInteger("varvalue".$i, 0);
			}
			for ($i=1; $i<=15; $i++)
			{
				$this->RegisterPropertyBoolean("modulinput".$i, false);
			}
			for ($i=1; $i<=15; $i++)
			{
				$this->RegisterPropertyString("value".$i, "");
			}
			for ($i=1; $i<=15; $i++)
			{
				$this->RegisterPropertyInteger("requestvarvalue".$i, 0);
			}
			for ($i=1; $i<=15; $i++)
			{
				$this->RegisterPropertyBoolean("modulrequest".$i, false);
			}
		}
	
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			//$idstring = $this->RegisterVariableString("FlowRequest", "Flow Request", "~String", 2);
			//IPS_SetHidden($idstring, true);
			
			
			
			$iftttass =  Array(
				Array(0, "Trigger Flow",  "Execute", -1)
				);
						
			$this->RegisterProfileIntegerAss("Flow.Trigger", "Execute", "", "", 0, 0, 0, 0, $iftttass);
			$this->RegisterVariableInteger("FlowTriggerButton", "Microsoft Flow Trigger Button", "Flow.Trigger", 1);
			$this->EnableAction("FlowTriggerButton");
			
			$this->ValidateConfiguration();	
		}
		
		private function ValidateConfiguration()
		{
			$change = false;
			
			$flowwebhook = $this->ReadPropertyString('flowwebhook');
			$selection = $this->ReadPropertyInteger("selection");
			$countsendvars = $this->ReadPropertyInteger("countsendvars");
			$countrequestvars = $this->ReadPropertyInteger("countrequestvars");
			
			
			
			if ($selection == 1 || $selection == 3) // Senden , Senden / Empfangen
			{
				//Webhook prüfen
				$webhookcheck = false;
				if ($flowwebhook == "")
					{
						$this->SetStatus(206); //Feld darf nicht leer sein
						//$this->SetStatus(104);
					}
				else
				{
					//Webhook Flow prüfen
					$webhookcheck = true;
				}
				
				if($countsendvars > 15)
					$countsendvars = 15;
				$varvaluecheck = false;
				$valuecheck = false;
				// Trigger Vars
				for ($i=1; $i<=$countsendvars; $i++)
				{
					${"varvalue".$i} = $this->ReadPropertyInteger('varvalue'.$i);
					${"modulinput".$i} = $this->ReadPropertyBoolean('modulinput'.$i);
					${"value".$i} = $this->ReadPropertyString('value'.$i);
					//Valuecheck
					if(${"modulinput".$i} === false && ${"varvalue".$i} === 0)
					{
						$errorid = 220+$i;
						$this->SetStatus($errorid); //Microsoft Flow Request: select a value or enter value  in module. , errorid 221 - 235
						break;
					}
					else
					{
						$varvaluecheck = true;
					}	
					//check Modul Value
					if (${"modulinput".$i} === true && ${"value".$i} === "")
					{
						$errorid = 240+$i;
						$this->SetStatus($errorid); // Microsoft Flow Request: missing value, enter value in field value, errorid 241 - 255
						break;
					}
					else
					{
						$valuecheck = true;
					}	
				}
				$checkformsend = false;
				if ($webhookcheck === true && $varvaluecheck === true && $valuecheck === true)
				{
					$checkformsend = true;
				}
				elseif ($webhookcheck === true && $countsendvars === 0)
				{
					$this->SetStatus(208); //Mindestens einen Wert auswählen
				}				
			}
			
			if ($selection == 2 || $selection == 3) // Empfang , Senden / Empfangen
			{
				if($countrequestvars > 15)
					$countrequestvars = 15;
				$reqvarvaluecheck = false;
				$checkformget = false;
				// Action Vars
				for ($i=1; $i<=$countrequestvars; $i++)
				{
					${"requestvarvalue".$i} = $this->ReadPropertyInteger("requestvarvalue".$i);
					${"modulrequest".$i} = $this->ReadPropertyBoolean("modulrequest".$i);
					$checkformget = false;
					//Valuecheck
					if(${"modulrequest".$i} === false && ${"requestvarvalue".$i} === 0)
					{
						$errorid = 260+$i;
						$this->SetStatus($errorid); //select a value or enter value in module, errorid 261 - 275
						break;
					}
					else
					{
						$checkformget = true;
					}		
				}
			}
			
			if ($selection == 1 && $checkformsend == true) // Senden
			{
				$this->SetStatus(102);
			}
			elseif ($selection == 2 && $checkformget == true) // Empfang
			{
				$this->SetStatus(102);
			}
			elseif ($selection == 3 && $checkformsend == true && $checkformget == true) // Senden / Empfangen
			{
				$this->SetStatus(102);
			}
		}	
		
		protected function SetRequestVariable($key, $value, $type, $i)
		{
			$ident = "FlowAktionVar".$i;
			$VarID = @$this->GetIDForIdent($ident);	
			if ($VarID === false)
				{
					$VarID = $this->CreateVarbyType($type, $i, $key);
				}
				
			$this->SetVarbyType($type, $VarID, $key, $value);	
		}
		
		protected function CreateVarbyType($type, $i, $key)
		{
			$ident = "FlowAktionVar".$i;
			if ($type == "string")
				{
					$VarID = $this->RegisterVariableString($ident, $key, "~String", $i);
				}
			elseif ($type == "integer")
				{
					$VarID = $this->RegisterVariableInteger($ident, $key, "", $i);
				}
			elseif ($type == "double") //float
				{
					$VarID = $this->RegisterVariableFloat($ident, $key, "", $i);
				}
			elseif ($type == "boolean")
				{
					$VarID = $this->RegisterVariableBoolean($ident, $key, "~Switch", $i);
				}
			elseif ($type == "NULL")
				{
					$VarID = NULL;
				}
				
				return $VarID;
		}
		
		protected function SetVarbyType($type, $VarID, $key, $value)
		{	
			if ($type == "string")
				{
					SetValueString($VarID, $value);
					IPS_SetInfo ($VarID, $key);
				}
			elseif ($type == "integer")
				{
					SetValueInteger($VarID, $value);
					IPS_SetInfo ($VarID, $key);
				}
			elseif ($type == "double") //float
				{
					SetValueFloat($VarID, $value);
					IPS_SetInfo ($VarID, $key);
				}
			elseif ($type == "boolean")
				{
					SetValueBoolean($VarID, $value);
					IPS_SetInfo ($VarID, $key);
				}
			elseif ($type == "NULL")
				{
					// nichts
				}
				
				return $VarID;
		}
		
		protected function WriteValues($valuesjson)
		{
			$values = json_decode($valuesjson, true);
			$countvalues = count($values);
			$countrequestvars = $this->ReadPropertyInteger('countrequestvars');
			if ( $countvalues == $countrequestvars)
			{
				$i = 1;
				foreach ($values as $key => $value)
					{
						$type = gettype($value);// Typ prüfen
						$requestvarvalue = $this->ReadPropertyInteger('requestvarvalue'.$i);  // Prüfen ob Modulvariable oder Var anlegen
						if (  $requestvarvalue == 0)
							{	
								$this->SetRequestVariable($key, $value, $type, $i);
							}
						else
							{
								$checkvartype = $this->CompareVartype($type, $requestvarvalue);
								if ($checkvartype)
								{
									SetValue($requestvarvalue, $value);
								}
								else
								{
									IPS_LogMessage("Flow:", "Es wurde kein Wert für ".$value." gesetzt, Variablentyp stimmt nicht mit Wert überein.");
								}
							}
						$i = $i+1;
					}
			 }
			else
			{
				echo "Die Anzahl der Variablen stimmt nicht mit der übermittelten Anzahl an Werten überein!";
				IPS_LogMessage("Flow:", "Es wurden keine Werte gesetzt.");
				IPS_LogMessage("Flow:", "Die Anzahl der Variablen stimmt nicht mit der übermittelten Anzahl an Werten überein!");
			}
		}
		
		protected function CompareVartype($type, $requestvarvalue)
		{
				$varinfo = (IPS_GetVariable($requestvarvalue));
				$vartype =  $varinfo["VariableType"];
				if ($vartype == 0) //bool
				{
					$ipsvartype = "boolean";
				}
				elseif ($vartype == 1) //integer
				{
					$ipsvartype = "integer";
				}
				elseif ($vartype == 2) //float
				{
					$ipsvartype = "double";
				}
				elseif ($vartype == 3) //string
				{
					$ipsvartype = "string";
				}
				
				if ($type ===  $ipsvartype)
				{
					return true;
				}
				else
				{
					return false;
				}
		}
		
		protected function SetupDataScript()
		{
			//prüfen ob Script existent
			$SkriptID = @$this->GetIDForIdent("FlowGetData");
				
			if ($SkriptID === false)
				{
					$SkriptID = $this->RegisterScript("FlowGetData", "Flow Get Data", $this->CreateDataScript(), 3);
					IPS_SetHidden($SkriptID, true);
					$this->SetFlowDataEvent($SkriptID);
				}
			else
				{
					//echo "Die Skript-ID lautet: ". $SkriptID;
				}	
		}
		
		protected function SetFlowDataEvent(integer $SkriptID)
		{
			//prüfen ob Event existent
			$ParentID = $SkriptID;

			$EreignisID = @($this->GetIDForIdent('EventFlowGetData'));
			if ($EreignisID === false)
				{
					$EreignisID = IPS_CreateEvent (0);
					IPS_SetName($EreignisID, "Event Flow Get Data");
					IPS_SetIdent ($EreignisID, "EventFlowGetData");
					IPS_SetEventTrigger($EreignisID, 0,  $this->GetIDForIdent('FlowRequest'));   //bei Variablenaktualisierung
					IPS_SetParent($EreignisID, $ParentID);
					IPS_SetEventActive($EreignisID, true);             //Ereignis aktivieren	
				}
				
			else
				{
				//echo "Die Ereignis-ID lautet: ". $EreignisID;	
				}
		}
		
		protected function CreateDataScript()
		{
			$Script = '<?
 $flowdatajson = GetValueString('.$this->GetIDForIdent("FlowRequest").');
 $flowdata = json_decode($flowdatajson); // Standard Objekt
 //$flowdata = json_decode($flowdatajson, true); // Array
 
 //Standard Objekt oder Array auslesen
 foreach ($flowdata as $key=>$data)
 {
 	 echo $key." => ".$data."\n";
	 //add command here
 }
 ?>';
			return $Script;
		}
		
		
		protected function ConvertVarString($objid)
		{
			$vartype = IPS_GetVariable($objid)['VariableType'];
			if ($vartype === 0)//Boolean
			{
			$value = GetValueBoolean($objid);// Boolean umwandeln in String
			$value = ($value) ? 'true' : 'false';
			}
			elseif($vartype === 1)//Integer
			{
				$value = strval(GetValueInteger($objid));   // Integer Umwandeln in String
			}
			elseif($vartype === 2)//Float
			{
				$value = strval(GetValueFloat($objid)); //Float umwandeln in String
			}
			elseif($vartype === 3)//String
			{
				$value = GetValue($objid);  //string ok
			}
			return $value;
			
		}
		
		

		public function TriggerFlow()
		{
			$flowwebhook = $this->ReadPropertyString('flowwebhook');
			$countsendvars = $this->ReadPropertyInteger("countsendvars");
			
			// Trigger Vars
			for ($i=1; $i<=$countsendvars; $i++)
			{
				${"modulinput".$i} = $this->ReadPropertyBoolean('modulinput'.$i);
				if (${"modulinput".$i})
				{
					${"value".$i} = $this->ReadPropertyString('value'.$i);
					${"key".$i} = "value".$i;
				}
				else 
				{
					${"objidvalue".$i} = $this->ReadPropertyInteger('varvalue'.$i);
					${"value".$i} = GetValue(${"objidvalue".$i});
					${"key".$i} = IPS_GetName(${"objidvalue".$i});
					//${"value".$i} = $this->ConvertVarString(${"objidvalue".$i});
				}
			}
			
			$values = array();
			for ($i=1; $i<=$countsendvars; $i++)
			{
				$values["value".$i] = ${"value".$i};
			}
			$values_string = json_encode($values);
			$flowreturn = $this->SendFlowTrigger($flowwebhook, $values_string);
			return $flowreturn;
		}


		public function SendFlowTrigger(string $flowwebhook, string $values)
		{
			
			$values = json_decode($values, true);
			$payload = array("flowwebhook" => $flowwebhook, "values" => $values);
						
			//an Splitter schicken
			$result = $this->SendDataToParent(json_encode(Array("DataID" => "{3F5C518E-C367-40AA-9231-479E50E914A4}", "Buffer" => $payload))); //Flow Interface GUI
			return $result;
		}
		
		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			$objectid = $data->Buffer->objectid;
			$values = $data->Buffer->values;
			$valuesjson = json_encode($values);
			if (($this->InstanceID) == $objectid)
			{
				//Parse and write values to our variables
				$this->WriteValues($valuesjson);
				//SetValue($this->GetIDForIdent("FlowRequest"), $valuesjson);
			}
		}
		
		public function RequestAction($Ident, $Value)
		{
			switch($Ident) {
				case "FlowTriggerButton":
					SetValue($this->GetIDForIdent("FlowTriggerButton"), $Value);
					$flowreturn = $this->TriggerFlow();
					
					break;	
				default:
					throw new Exception("Invalid ident");
			}
		}
		
		protected function GetUsernamePassword()
		{
			$objid = $this->GetIOObjectID();
			$username = IPS_GetProperty($objid, "username");
			$password = IPS_GetProperty($objid, "password");
			$webhooksettings = array ("username" => $username, "password" => $password);
			return $webhooksettings;		
		}


		protected function GetIOObjectID()
		{
			$InstanzenListe = IPS_GetInstanceListByModuleID("{09A63600-4313-4CE9-8875-8F64A44C5D5E}");
			foreach ($InstanzenListe as $InstanzID)
				{
					return $InstanzID;
				}
		}
				
		//Configuration Form
		public function GetConfigurationForm()
		{
			$selection = $this->ReadPropertyInteger("selection");
			$countsendvars = $this->ReadPropertyInteger("countsendvars");
			$countrequestvars = $this->ReadPropertyInteger("countrequestvars");
			$formhead = $this->FormHead();
			$formstatus = $this->FormStatus();
			$formsend = $this->FormSend($countsendvars);
			$formget = $this->FormGet($countrequestvars);
			/*
			if ($selection == 2)
			{
				$formget = substr($this->FormGet($countrequestvars), 0, -1); // letztes Komma entfernen
			}
			else
			{
				$formget = $this->FormGet($countrequestvars);
			}
			*/
			$formelementsend = '{ "type": "Label", "label": "__________________________________________________________________________________________________" }';
			if($selection == 0)// keine Auswahl
			{
				return	'{ '.$formhead.'],'.$formstatus.' }';
			}
			
			elseif ($selection == 1) // Senden 
			{
				$formactions = $this->FormActions(1, $countrequestvars);
				return	'{ '.$formhead.','.$formsend.$formelementsend.'],'.$formactions.','.$formstatus.' }';
			}
			
			elseif ($selection == 2) // Empfangen 
			{
				$formactions = $this->FormActions(2, $countrequestvars);
				return	'{ '.$formhead.','.$formget.$formelementsend.'],'.$formactions.','.$formstatus.' }';
			}
			
			elseif ($selection == 3) // Senden / Empfangen
			{
				$formactions = $this->FormActions(3, $countrequestvars);
				return	'{ '.$formhead.','.$formsend.$formget.$formelementsend.'],'.$formactions.','.$formstatus.' }';
			}
		
		}
		
		protected function FormSend($countsendvars)
		{
			$form = '{ "type": "Label", "label": "Microsoft Flow Request____________________________________________________________________________________________" },
			{ "type": "Label", "label": "HTTP POST URL, Microsoft Flow type request" },
		{ "name": "flowwebhook", "type": "ValidationTextBox", "caption": "HTTP POST URL" },
		{ "type": "Label", "label": "number of variables for a request in Microsoft Flow (max 15)" },
		{ "type": "NumberSpinner", "name": "countsendvars", "caption": "number of variables" },'
		.$this->FormSendVars($countsendvars);
			return $form;
		}
		
		protected function FormSendVars($countsendvars)
		{
			if ($countsendvars > 0)
			{
				if($countsendvars > 15)
				$countsendvars = 15;
				$form = '{ "type": "Label", "label": "variables with values for Mircosoft Flow Request" },';
				for ($i=1; $i<=$countsendvars; $i++)
				{
					$form .= '{ "type": "SelectVariable", "name": "varvalue'.$i.'", "caption": "value '.$i.'" },';
				}
				$form .= '{ "type": "Label", "label": "alternative leave variable empty und click check mark" },';
				for ($i=1; $i<=$countsendvars; $i++)
				{
					$form .= '{
						"name": "modulinput'.$i.'",
						"type": "CheckBox",
						"caption": "use modul value '.$i.'"
					},	
			{ "name": "value'.$i.'", "type": "ValidationTextBox", "caption": "value '.$i.'" },';
				}
			}
			else
			{
				$form = "";
			}
			return $form;
		}
		
		protected function FormGet($countrequestvars)
		{			 
			$form = '{ "type": "Label", "label": "Microsoft Flow HTTP_______________________________________________________________________________________________" },
			{ "type": "Label", "label": "variables with values for Microsoft Flow HTTP" },
			{ "type": "Label", "label": "number of variables for a HTTP Flow from Microsoft Flow (max 15)" },
			{ "type": "NumberSpinner", "name": "countrequestvars", "caption": "number of variables" },'
			.$this->FormGetVars($countrequestvars);
			return $form;
		}
		
		protected function FormGetVars($countrequestvars)
		{
			if($countrequestvars > 15)
				$countrequestvars = 15;
			$form = '';
			for ($i=1; $i<=$countrequestvars; $i++)
			{
				$form .= '{ "type": "SelectVariable", "name": "requestvarvalue'.$i.'", "caption": "value '.$i.'" },';
			}
			$form .= '{ "type": "Label", "label": "alternative leave variable empty und click check mark for creating a new variable" },';
			for ($i=1; $i<=$countrequestvars; $i++)
			{
				$form .= '{
                    "name": "modulrequest'.$i.'",
                    "type": "CheckBox",
                    "caption": "module create variable for value '.$i.'"
                },';
			}
			return $form;
		}
		
		protected function FormHead()
		{
			$form = '"elements":
	[
		{ "type": "Label", "label": "Connection from IP-Symcon to Microsoft Flow" },
		{ "type": "Label", "label": "https://flow.microsoft.com" },
		{ "type": "Label", "label": "communication type with Microsoft Flow: send, receive, send/receive" },
		{ "type": "Select", "name": "selection", "caption": "communication",
    "options": [
        { "label": "Please select", "value": 0 },
        { "label": "Send", "value": 1 },
        { "label": "Receive", "value": 2 },
        { "label": "Send/Receive", "value": 3 }
    ]
}';
			// End ]
			return $form;
		}
		
		protected function FormActions($type, $countrequestvars)
		{
			if ($type == 1) // Senden
			{
				$form = '"actions": [{ "type": "Label", "label": "request configuration Microsoft Flow:" },
				{ "type": "Label", "label": " - My Flows" },
				{ "type": "Label", "label": " - generate new without template" },
				{ "type": "Label", "label": " - choose request" },
				{ "type": "Label", "label": " - JSON Schema:" },
				{ "type": "Label", "label": " - next step" },
				
				{ "type": "Label", "label": " - choose next steps" },
				{ "type": "Label", "label": " - after finishing flow, open flow again for editing request and copy the URL in this module" },
				{ "type": "Label", "label": "______________________________________________________________________________________________________" },
				{ "type": "Label", "label": "Trigger Microsoft Flow" },
				{ "type": "Button", "label": "Trigger Flow", "onClick": "Flow_TriggerFlow($id);" } ]';
				return  $form;
			}
			elseif ($type == 2) // Empfangen
			{
				$form = '"actions": [ { "type": "Label", "label": "HTTP configuration Flow: " },
				{ "type": "Label", "label": " - Method:" },
				{ "type": "Label", "label": "     POST " },
				{ "type": "Label", "label": " - URI:" },
				{ "type": "Label", "label": "     '.$this->GetIPSConnect().'/hook/flow" },
				{ "type": "Label", "label": " - Header:" },
				{ "type": "Label", "label": "     {" },
				{ "type": "Label", "label": "      \"charset\":\"utf-8\"," },
				{ "type": "Label", "label": "      \"Content-Type\":\"application/json\"," },
				{ "type": "Label", "label": "     }" },
				{ "type": "Label", "label": " - Body: (example)" },
				{ "type": "Label", "label": "     {" },
				{ "type": "Label", "label": "     {\"objectid\":'.$this->InstanceID.',\"values\":{\"keyvalue1\":\"value1string\",\"keyvalue2\":value2float,\"keyvalue3\":value3int,\"keyvalue4\":value4bool}}"},
				{ "type": "Label", "label": "     }" },
				{ "type": "Label", "label": "     example values begin and end with curly brackets" },
				{ "type": "Label", "label": "     put keys always inside \"\", string value inside \"\", boolean, integer and float values without \"\"" },	
				{ "type": "Label", "label": "     show advanced options" },
				{ "type": "Label", "label": "     username (standard ipsymcon), set username in Flow IO" },
				{ "type": "Label", "label": "     password is set, for individual password set password in Flow IO" },
				{ "type": "Label", "label": " - Authentification:" },
				{ "type": "Label", "label": "     {" },
				{ "type": "Label", "label": "      \"type\":\"Basic\"," },
				{ "type": "Label", "label": "      \"username\":\"'.$this->FlowConfigAuthUser().'\"," },
				{ "type": "Label", "label": "      \"password\":\"'.$this->FlowConfigAuthPassword().'\"," },
				{ "type": "Label", "label": "     }" } ]';
				return  $form;
			}
			
			elseif ($type == 3) // Senden / Empfangen
			{
				$form = '"actions": [{ "type": "Label", "label": "configuration Microsoft Flow:" },
				{ "type": "Label", "label": "request configuration Microsoft Flow:" },
				{ "type": "Label", "label": " - My Flows" },
				{ "type": "Label", "label": " - generate new without template" },
				{ "type": "Label", "label": " - choose request" },
				{ "type": "Label", "label": " - JSON Schema:" },
				{ "type": "Label", "label": " - next step" },
				
				{ "type": "Label", "label": " - choose next steps" },
				{ "type": "Label", "label": " - after finishing flow, open flow again for editing request and copy the URL in this module" },
				{ "type": "Label", "label": "______________________________________________________________________________________________________" },
				{ "type": "Label", "label": "HTTP configuration Flow: " },
				{ "type": "Label", "label": " - Method:" },
				{ "type": "Label", "label": "     POST " },
				{ "type": "Label", "label": " - URI:" },
				{ "type": "Label", "label": "     '.$this->GetIPSConnect().'/hook/flow" },
				{ "type": "Label", "label": " - Header:" },
				{ "type": "Label", "label": "     {" },
				{ "type": "Label", "label": "      \"charset\":\"utf-8\"," },
				{ "type": "Label", "label": "      \"Content-Type\":\"application/json\"" },
				{ "type": "Label", "label": "     }" },
				{ "type": "Label", "label": " - Body: (example)" },
				{ "type": "Label", "label": "     {" },
				{ "type": "Label", "label": "       \"objectid\":'.$this->InstanceID.',\"values\":{\"keyvalue1\":\"value1string\",\"keyvalue2\":value2float,\"keyvalue3\":value3int,\"keyvalue4\":value4bool}"},
				{ "type": "Label", "label": "     }" },
				{ "type": "Label", "label": "     example values begin and end with curly brackets" },
				{ "type": "Label", "label": "     put keys always inside \"\", string value inside \"\", boolean, integer and float values without \"\"" },
				{ "type": "Label", "label": "     show advanced options" },
				{ "type": "Label", "label": "     username (standard ipsymcon), set username in Flow IO" },
				{ "type": "Label", "label": "     password is set, for individual password set password in Flow IO" },
				{ "type": "Label", "label": " - Authentification:" },
				{ "type": "Label", "label": "     {" },
				{ "type": "Label", "label": "      \"type\":\"Basic\"," },
				{ "type": "Label", "label": "      \"username\":\"'.$this->FlowConfigAuthUser().'\"," },
				{ "type": "Label", "label": "      \"password\":\"'.$this->FlowConfigAuthPassword().'\"" },
				{ "type": "Label", "label": "     }" },
				{ "type": "Label", "label": "______________________________________________________________________________________________________" },
				{ "type": "Label", "label": "Trigger Microsoft Flow" },
				{ "type": "Button", "label": "Trigger Flow", "onClick": "Flow_TriggerFlow($id);" } ]';
				return  $form;
			}
		}
			
		protected function FlowConfigRequest($countrequestvars)
		{
			$webhooksettings =	GetUsernamePassword();
			$username = $webhooksettings["username"];
			$password = $webhooksettings["password"];
			if ($countrequestvars == 0)
			{
				$form =  '{ "type": "Label", "label": "         values  please select at least one value" }';
			}
			else
			{	
				//{"actions":[ {"type":"Label","label":"values     {\"value1\":\"value2\",\"value3\":\"value4\"}"} ]}
				$form =  '{ "type": "Label", "label": "         values              {';
				for ($i=1; $i<=4; $i++)
				{
					$form .= "\\\"keyvalue".$i."\\\":\\\"value".$i."\\\",";
				}
				$form = substr($form, 0, -1);
				$form .= ' }"},';
			}
			return $form;
		}
		
		protected function FlowConfigAuthUser()
		{
			$webhooksettings =	$this->GetUsernamePassword();
			$username = $webhooksettings["username"];
			$password = $webhooksettings["password"];
			return $username;
		}
		
		protected function FlowConfigAuthPassword()
		{
			$webhooksettings =	$this->GetUsernamePassword();
			$password = $webhooksettings["password"];
			return $password;
		}
		
		protected function FormStatus()
		{
			$form = '"status":
            [
                {
                    "code": 101,
                    "icon": "inactive",
                    "caption": "Creating instance."
                },
				{
                    "code": 102,
                    "icon": "active",
                    "caption": "Flow created."
                },
				'.$this->FormStatusErrorSelectorEnterHTTP().'
                {
                    "code": 104,
                    "icon": "inactive",
                    "caption": "interface closed."
                },
				{
                    "code": 201,
                    "icon": "inactive",
                    "caption": "select number of values in module."
                },
				'.$this->FormStatusErrorSelectorEnter().'
				{
                    "code": 206,
                    "icon": "error",
                    "caption": "HTTP POST URL field must not be empty."
                },
				'.$this->FormStatusErrorMissingValueinField().'
				{
                    "code": 207,
                    "icon": "error",
                    "caption": "Flow URL not valid."
                },
				{
                    "code": 208,
                    "icon": "error",
                    "caption": "Select min one Value."
                }
			
            ]';
			return $form;
		}

		protected function FormStatusErrorSelectorEnter() // errorid 221 - 235
		{
			$form = "";
			for ($i=1; $i<=15; $i++)
			{
				$errorid = 220+$i;
				$form .= '{
                    "code": '.$errorid.',
                    "icon": "error",
                    "caption": "Microsoft Flow Request: select a value '.$i.' or enter value '.$i.' in module."
                },'; 
			}
			return $form;
		}
		
		protected function FormStatusErrorMissingValueinField() // errorid 241 - 255
		{
			$form = "";
			for ($i=1; $i<=15; $i++)
			{
				$errorid = 240+$i;
				$form .= '{
                    "code": '.$errorid.',
                    "icon": "error",
                    "caption": "Microsoft Flow Request: missing value, enter value in field value '.$i.'"
                },'; 
			}
			return $form;
		}
		
		protected function FormStatusErrorSelectorEnterHTTP() // errorid 261 - 275
		{
			$form = "";
			for ($i=1; $i<=15; $i++)
			{
				$errorid = 260+$i;
				$form .= '{
                    "code": '.$errorid.',
                    "icon": "error",
                    "caption": "Microsoft Flow HTTP: select a value '.$i.' or enter value '.$i.' in module."
                },'; 
			}
			return $form;
		}
		
	
		// IP-Symcon Connect auslesen
		protected function GetIPSConnect()
		{
			$InstanzenListe = IPS_GetInstanceListByModuleID("{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}");
			foreach ($InstanzenListe as $InstanzID) {
				$ConnectControl = $InstanzID;
			} 
			$connectinfo = CC_GetUrl($ConnectControl);
			if ($connectinfo == false || $connectinfo == "")
				$connectinfo = 'https://<IP-Symcon Connect>.ipmagic.de';
			return $connectinfo;
		}
		
		
		
		//Profile
		protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
		{
			
			if(!IPS_VariableProfileExists($Name)) {
				IPS_CreateVariableProfile($Name, 1);
			} else {
				$profile = IPS_GetVariableProfile($Name);
				if($profile['ProfileType'] != 1)
				throw new Exception("Variable profile type does not match for profile ".$Name);
			}
			
			IPS_SetVariableProfileIcon($Name, $Icon);
			IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
			IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
			IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
			
		}
		
		protected function RegisterProfileIntegerAss($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Associations)
		{
			if ( sizeof($Associations) === 0 ){
				$MinValue = 0;
				$MaxValue = 0;
			} 
			/*
			else {
				//undefiened offset
				$MinValue = $Associations[0][0];
				$MaxValue = $Associations[sizeof($Associations)-1][0];
			}
			*/
			$this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits);
			
			//boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
			foreach($Associations as $Association) {
				IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
			}
			
		}
	
	}

?>
