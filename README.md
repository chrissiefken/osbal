osbal
=====

Open Source fully featured Loadbalancer stack with configuration gui based on Twitter Bootstrap, Heartbeat, HA Proxy and stunnel.

##Step 1:
Set up at least one plain Ubuntu LTS server - our prefered system is currently 14.04
You want long term security updates so you can leave yor system in production and continue to patch for security updates.
If you require high availabiltiy through out your stack you should set 2 of these up.

##Step 2:
Give each server a unique private IP - these will be our management IP's.

##Step 3: 
Run `sudo apt-get install heartbeat haproxy`
