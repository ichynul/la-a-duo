<pre>
<code>install   </code>:    Create      `/app/$prefix` dir and create routes.php and controllers
<code>route     </code>:    Create      `/app/$prefix/extroutes.php`
<code>build     </code>:    Create      `/database/migrations/$prefix/2016_01_04_173148_create_admin_tables_$prefix.php` and `migrate`.
<code>seed      </code>:    Seed        `AdminTablesSeeder` seed admin tables(users,rols,menus...), if table is not empty, will pass it
</pre>