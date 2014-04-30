osbal
=====

Open Source fully featured Loadbalancer stack with configuration gui based on Twitter Bootstrap, Heartbeat, HAProxy and stunnel.

##Goals
Build a feature rich highly available layer 7 _appliance_ which does load balancing and can be configured via a web browser. The intent of this is to allow for network admins to have a commercially viable alternative to closed source appliances otherwise on the market and not have to invest the time learning how to configure HAProxy, stunnel and Heartbeat.

While we could have used different technology to implement the UI components which are lighter to allow for a lower system footprint, its our belief that ease of installation and management are core to our mission.

Ideally this set up should work for very limited hardware, with a 2gb footprint it should even run on a Raspberry Pi.

##Getting started

###Step 1:
Set up at least one plain Ubuntu LTS server - our prefered system is currently 14.04
You want long term security updates so you can leave yor system in production and continue to patch for security updates.
If you require high availability throughout your stack you should set 2 of these up.

###Step 2:
Give each server a unique private IP - these will be our management IP's.

###Step 3: 
Install the required software - Run `sudo apt-get install heartbeat haproxy stunnel4 apache2 php5`

###Step 4:
Download the UI from git hub.

###Step 5:
Point your browser to the IP address of your new Loadbalancer and follow the directions!
