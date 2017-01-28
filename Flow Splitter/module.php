<?

class FlowSplitter extends IPSModule
{

    public function Create()
    {
	//Never delete this line!
        parent::Create();
		
		//These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
		$this->RequireParent("{09A63600-4313-4CE9-8875-8F64A44C5D5E}", "Flow I/O"); //Flow I/O		

    }

    public function ApplyChanges()
    {
	//Never delete this line!
        parent::ApplyChanges();
        $change = false;
		
		/*
		$this->RegisterVariableString("BufferIN", "BufferIN", "", 1);
        $this->RegisterVariableString("CommandOut", "CommandOut", "", 2);
        IPS_SetHidden($this->GetIDForIdent('CommandOut'), true);
        IPS_SetHidden($this->GetIDForIdent('BufferIN'), true);
		*/
		$ParentID = $this->GetParent();
		
		$this->SetStatus(102);
		/*
		$ParentID = $this->GetParent();
					
		// Wenn I/O verbunden ist
		if ($this->HasActiveParent($ParentID))
			{
				//Instanz aktiv
			}
		*/	
    }

		/**
        * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
        * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
        *
        *
        */
	protected $debug = false;
	
	

	################## DUMMYS / WOARKAROUNDS - protected

    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }

    protected function HasActiveParent($ParentID)
    {
        if ($ParentID > 0)
        {
            $parent = IPS_GetInstance($ParentID);
            if ($parent['InstanceStatus'] == 102)
            {
                $this->SetStatus(102);
                return true;
            }
        }
        $this->SetStatus(203);
        return false;
    }

    private function SetValueBoolean($Ident, $value)
    {
        $id = $this->GetIDForIdent($Ident);
        if (GetValueBoolean($id) <> $value)
        {
            SetValueBoolean($id, $value);
            return true;
        }
        return false;
    }

    private function SetValueInteger($Ident, $value)
    {
        $id = $this->GetIDForIdent($Ident);
        if (GetValueInteger($id) <> $value)
        {
            SetValueInteger($id, $value);
            return true;
        }
        return false;
    }

    private function SetValueString($Ident, $value)
    {
        $id = $this->GetIDForIdent($Ident);
        if (GetValueString($id) <> $value)
        {
            SetValueString($id, $value);
            return true;
        }
        return false;
    }

    protected function SetStatus($InstanceStatus)
    {
        if ($InstanceStatus <> IPS_GetInstance($this->InstanceID)['InstanceStatus'])
            parent::SetStatus($InstanceStatus);
    }

	
	// Data an Child weitergeben
	public function ReceiveData($JSONString)
	{
	 
		// Empfangene Daten vom Flow I/O
		$data = json_decode($JSONString);
		//$dataio = json_encode($data->Buffer);
		//SetValueString($this->GetIDForIdent("BufferIN"), $dataio);
		
		
		//IPS_LogMessage("ReceiveData Flow Splitter", utf8_decode($data->Buffer)); //utf8_decode geht nur bei string
	 
		// Hier werden die Daten verarbeitet
	 
		// Weiterleitung zu allen Gerät-/Device-Instanzen
		$this->SendDataToChildren(json_encode(Array("DataID" => "{C1269E03-7292-4406-88E1-E9A8BEE8795F}", "Buffer" => $data->Buffer))); //Flow Splitter Interface GUI
	}
	
			
	################## DATAPOINT RECEIVE FROM CHILD
	

	public function ForwardData($JSONString)
	{
	 
		// Empfangene Daten von der Device Instanz
		$data = json_decode($JSONString);
		$debug = false;
		$datasend = $data->Buffer;
		$datasend = json_encode($datasend);
		if($debug)
		{
			IPS_LogMessage("Flow Splitter Forward Data", $datasend);
		}
		//SetValueString($this->GetIDForIdent("CommandOut"), $datasend);
	 
		// Hier würde man den Buffer im Normalfall verarbeiten
		// z.B. CRC prüfen, in Einzelteile zerlegen
		try
		{
			//
		}
		catch (Exception $ex)
		{
			echo $ex->getMessage();
			echo ' in '.$ex->getFile().' line: '.$ex->getLine().'.';
		}
	 
		// Weiterleiten zur I/O Instanz
		$resultat = $this->SendDataToParent(json_encode(Array("DataID" => "{57D4EC3C-E4DC-4CE7-8819-A228B08E94DE}", "Buffer" => $data->Buffer))); //TX GUI
	 
		// Weiterverarbeiten und durchreichen
		return $resultat;
	 
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
}

?>