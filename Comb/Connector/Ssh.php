<?php
class Comb_Connector_Ssh implements Comb_ConnectorInterface
{
    /**
     * Runs the command on the servers belonging to the serverLists provided
     * @param string $command the command to run
     * @param array $serverLists the serverslists where to run the command on
     */
    public function execCommand($command, Array $serverLists)
    {
        $servers = $this->getServersForServerLists($serverLists);

        $waitFor = array();

        foreach($servers as $server) {
            if (false === $server->isConnected() && false === $server->connect()) {
                Comb_Registry::get('logger')->warning('Skipping server');
                continue;
            }
            
            $server->exec($command);
            $waitFor[] = $server;
        }

        while(false === $this->allDone($waitFor)) {
            usleep('100000');
        }
    }

    /**
     * Checks all the servers to see if they are done
     * @param array $waitFor the servers to wait for
     * @return boolean true if everything is done and we can continue, false if not
     */
    protected function allDone(Array $waitFor=array())
    {
        $done = true;
        foreach($waitFor as &$server) {
            $server->updateLastRequestStatus();
            $requestStatus = $server->getLastRequestStatus();
            
            if ($requestStatus == Comb_Connector_SshConnection::STATUS_SUCCESS ||
                $requestStatus == Comb_Connector_SshConnection::STATUS_READY) {
                unset($server);
                continue;
            }

            if ($requestStatus == Comb_Connector_SshConnection::STATUS_ERROR) {
                Comb_Registry::get('logger')->notice('To be implemented: rollback');
                continue;
            }
        }
        return $done;
    }

    /**
     * Returns the servers that belong to the serverlists provided. If a server
     * belongs to multiple serverLists we don't return doubles.
     * @param array $serverLists Array containing Comb_Connector_SshConnection objects
     * @return array An array with Comb_Connector_SshConnection objects
     * 
     * @todo replace dummy content with config reader loader super awesome thingy
     */
    protected function getServersForServerLists(Array $serverLists)
    {
        $obj = new Comb_Connector_SshConnection('webserver01', 'username', 'password');
        $obj2 = new Comb_Connector_SshConnection('webserver02', 'username', 'password');
        return array($obj, $obj2);
    }
}