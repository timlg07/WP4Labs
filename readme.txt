=== WP4Labs ===
Contributors: Philipp Franck
Donate link: -
Tags: users, academic
Requires at least: 3.1.0
Tested up to: 3.1.1
Stable tag: 1.6

Adds some lightweight features to manage scientific groups and users.

== Description ==

* Use Wordpress 3.1 - Older Versions will cause trouble

WP4Labs is a small Plugin, wich provides some functions to manange a lab or a scientific group. I designed it for the websites of two Biolabs. It's far away from being my first WP-Plugin, but it's the first one I want to publish. I tested it in four or five WP-installtions on dofferent servers, and it seems to all OK.

It provides the following parts:
- An advanced User Profile, containing some fields for academic career dates
- in connection with that a connection to the ariw.org-database of scientific institutions wordl wide, to make it easer finding a specific institution and it's URL
- the possibility to manage users in academic groups, such as Alumni, Post-Doc, P.I. and such (customizable)
- a local-avatar function which maintains the possibility for users to use a Gravatar (maybe someday I should take this one out and make a own plugin out of it)
- a post-type called 'project', to which you can connect users to (scientific) groups. Only users with P.I.-status (for being leader of the group) can add or remove users.

A nice backend integration is built, I also ship some example templates to learn, how to integrate the provided functions to your theme.

== Installation ==
1) Install plugin-files.
2) To enable caching of ariw.org-files got the plugin's directory and give the wordpress writing rights for the folder 'ariw_cache' (777).
3) Go to site's backend and add a project. Add some users to the project.
4) Build templates for your theme (see usage or example templates)

== Frequently Asked Questions ==

= Where is the advanced user profile? =
Build a new user. Save it. Now edit it. Now the new field should be visible.

= Ariw.org-Database is not reachable =
The plugin tries two ways of connecting to the Database. Under some circumstances both fail, due to security settings of your server. In that case, I can't anything for you. 


== Usage ==

For your template, build the following templates:
archive-biofoo_project.php  - This gives an overview of the existing projects. 
single-biofoo_project.php - Watch a single project.

Also you can edit your author.php to display the additional information.

The plugin contains some useful functions building these templates:
<user_object> = get_biofoo(<user_object>);
Throw a <user_object> from get_userinfo (not get_users!) or such into it, to get the advanced profile information.
Example:
<?php $user = get_biofoo(get_userdata($user_id));
 ?>
<?php if (($user->ba_degree or $user->ma_degree) or $user->phd_degree) : ?>
<h4><?php echo _e('Academic Degrees'); ?></h4>
<table class="user_info">
<?php if ($user->ba_degree) : ?><tr><td>B.A.</td><td><?php echo $user->ba_degree; ?></td></tr><?php endif; ?>
<?php if ($user->ma_degree) : ?><tr><td>M.A.</td><td><?php echo $user->ma_degree; ?></td></tr><?php endif; ?>
<?php if ($user->phd_degree) : ?><tr><td>Ph.D.</td><td><?php echo $user->phd_degree; ?></td></tr><?php endif; ?>
</table>
<?php endif; ?>

--------------------------

echo_project_members(<post_id>, <style>, <allgroups>)

Displays (or returns) alls members of a project:

<post_id> INT or STRING
The Id of a post with type biofoo_project. Instead of a post_id you may use the string keyword 'all' to get all users of the blog.

<style> STRING
Default: boxes.
Knows the following styles:
* 'longlist' -> All members below each other with avatar and academic information.
* 'boxes' -> Boxes with 
* 'text' -> The names of all members in a line as clickable links.
* 'return' -> Displays nothing but returns an array with the members of the project.

<allgroups> BOOLEAN
Default: true
If set to false, users, which are in passive user groups like alumni are not displayed.
--------------------------

the_boss(<post_id>)
Displays the leading members (P.I.s) of a project.

--------------------------

function list_users_projects(<user_id>)
Displays a list of projects, a user is member of.


== Screenshots ==

1. The advanced user profile
2. Adding a new Project
3. The frontend of a Project may look like this
4. The frontend of the user list may look like that

== Update Log ==

1.5
* Renamed file and folder to wp4labs.

