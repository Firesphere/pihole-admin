# pihole-admin

PiHole admin on Slim 4

# Installation

git clone this repository

Run `composer install --no-dev --prefer-dist`

Create a webserver-writeable folder `mkdir -p public/cache`

You should now be ready to go.

# Work in progress

The goal is to rebuild/recreate the PiHole admin interface, but using Slim4 framework, to make it more extensible and configurable.

Todos matching Pi-hole:
- [x] Dashboard
- [x] Query log
- [x] Long Term data
- [x] Groups etc.
- [x] Network
- [x] Enable/Disable blocking
- [x] DNS Record management
- [x] Tooling
- [ ] Settings
  - [x] System
  - [x] DNS
  - [x] DHCP
  - [ ] API/Web interface
  - [ ] Privacy
  - [ ] Teleporter

Todos - Additional
- [ ] User management
  - [ ] User permissions
  - [ ] Link devices to users
- [ ] Exclude domains from stats
- [ ] Dashboard modules management
  - Optional additional Dashboard modules
  - [ ] Speedtest
 


# Adding modules

Create a module like `ClientActivity`;

Register it
`Module::registerModule("My\\Namespaced\\Class")` or `Module::registerModule(MyClass::class)`

Set the sort property to wherever you want it sorted

Add the folder and template location to where you want it.

You should be set to go and your module should be included now.

