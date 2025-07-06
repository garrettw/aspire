# Aspire PHP Framework

My goal is for Aspire to be the best general-purpose PHP app framework out there.

Currently targeting: PHP 5.6+

In an age of front-end JavaScript frameworks like Angular, Backbone, React, Vue,
Ember, ExtJs, and the like, where the code running on the server is just a dumb ReST API,
I believe there is still a place alongside them for apps that run primarily on
purpose-built web servers. These apps are not complex; they more closely resemble
web pages than a piece of software, and a large portion of the web still looks
and works that way, and will remain that way for the foreseeable future. That's
not a bad thing. Aspire aims to give those webmasters a leg up.

## Defining characteristics (in priority order)
 - Hybrid architecture (ADR + the ViewModel from MVVM)
 - Opinionated regarding use of best practices (I want this to be a model for the community)
 - True, full object-oriented code
 - Complete class modularity
 - Standards-compliant code that is easy to read, write, and understand
 - Performant and scalable
 - Behavior-driven development (BDD)
 - User authentication/ACL functionality included as an integral component
 - Small, limited-functionality core that is easily extensible
 - Use of libraries from the community unless a suitable one does not exist
 - Centered around Composer and Github but includes an installer for non-CLI environments

## File structure

- doc/      (auto-generated API documentation)
- features/ (API behavior specs in plain English)
- src/      (all core framework code)
- spec/     (testable code behavior specs)

## Planning

- ORM (data mapper): Level-2/Maphper
- Router: crysalead/router
- Templating engine: Level-2/Transphporm
- Need to find good authentication and ACL libs

## Contributing

Any contributions are welcomed and requested. Help me make this thing awesome!