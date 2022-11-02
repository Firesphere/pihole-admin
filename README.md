# pihole-admin

PiHole admin on Slim 4

# Installation

git clone this repository

Run `composer install --no-dev --prefer-dist`

Create a webserver-writeable folder `mkdir -p public/cache`

You should now be ready to go.

# Work in progress

The goal is to rebuild/recreate the PiHole admin interface, but using Slim4 framework, to make it more extensible and configurable.

Todos:
- [ ] API*
- [ ] Interface*
- [x] DNS Record management*
- [ ] User management
- [ ] Preferences
- [x] Enable/disable features/PiHole
- [ ] Update from interface
- [ ] Manage DHCP/DNS
- [ ] Import/export
- [x] Javascript calls updates
- [x] Group and system management
- [x] Adlist management
- [ ] Logging outputs
- [x] Network list


* Partially done, e.g. some calls or options exist but need frontend implementations

# Adding modules

Create a module like `ClientActivity`;

Register it
`Module::registerModule("My\\Namespaced\\Class")` or `Module::registerModule(MyClass::class)`

Set the sort property to wherever you want it sorted

Override the folder and template location to where you want it.

You should be set to go and your module should be included now.

