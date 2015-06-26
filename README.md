Graphite datasource for Network Weathermap
==========================================

Simple plugin for php-weathermap that adds the ability to source information from Graphite

Install to `lib/datasources`

Datasources are formatted like: graphite:graphite_url/metricin:metricout
      e.g. graphite:system.example.com:8081/devices.servers.XXXXX.snmp.rx:devices.servers.XXXXX.snmp.tx
