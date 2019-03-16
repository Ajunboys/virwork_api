<?php

namespace OCA\Virwork_API\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

  class Version000000Date20181224140601 extends SimpleMigrationStep {

      /**
        * @param IOutput $output
        * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
        * @param array $options
        * @return null|ISchemaWrapper
       */
       public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
          /** @var ISchemaWrapper $schema */
          $schema = $schemaClosure();

          if (!$schema->hasTable('virwork_auth')) {
              $table = $schema->createTable('virwork_auth');
              $table->addColumn('id', 'integer', [
                  'autoincrement' => true,
                  'notnull' => true,
              ]);
              $table->addColumn('username', 'string', [
                  'notnull' => true,
                  'length' => 64,
              ]);
              $table->addColumn('password', 'string', [
                  'notnull' => true,
                  'length' => 2000,
              ]);

              $table->addColumn('client_token', 'string', [
                  'notnull' => true,
                  'length' => 512,
              ]);
              

              $table->setPrimaryKey(['id']);
              $table->addIndex(['username'], 'virwork_auth_id_idx');
          }


          if (!$schema->hasTable('virwork_auth_group_access')) {
              $table = $schema->createTable('virwork_auth_group_access');
              $table->addColumn('id', 'integer', [
                  'autoincrement' => true,
                  'notnull' => true,
              ]);
              $table->addColumn('group', 'string', [
                  'notnull' => true,
                  'length' => 64,
              ]);

              $table->addColumn('vvwork_group_id', 'integer', [
                  'notnull' => true,
                  'length' => 11,
              ]);
              ]);
              $table->addColumn('quota', 'string', [
                  'notnull' => false,
                  'length' => 32,
              ]);
              $table->addColumn('upload', 'integer', [
                  'notnull' => true,
              ]);
              $table->addColumn('download', 'integer', [
                  'notnull' => true,
              ]);
              $table->addColumn('delete', 'integer', [
                  'notnull' => true,
              ]);
              $table->addColumn('local_share', 'integer', [
                  'notnull' => true,
              ]);
              $table->addColumn('public_share', 'integer', [
                  'notnull' => true,
              ]);

              $table->setPrimaryKey(['id']);
              $table->addIndex(['group'], 'virwork_auth_group_access_id_idx');
          }


          if (!$schema->hasTable('virwork_auth_group')) {
              $table = $schema->createTable('virwork_auth_group');
              $table->addColumn('id', 'integer', [
                  'autoincrement' => true,
                  'notnull' => true,
              ]);
              $table->addColumn('group', 'string', [
                  'notnull' => true,
                  'length' => 64,
              ]);
              $table->addColumn('vvwork_group_id', 'integer', [
                  'notnull' => false,
              ]);
              $table->setPrimaryKey(['id']);
              $table->addIndex(['group'], 'virwork_auth_group_id_idx');
          }
          

          if (!$schema->hasTable('virwork_auth_group_rls')) {
              $table = $schema->createTable('virwork_auth_group_rls');
              $table->addColumn('id', 'integer', [
                  'autoincrement' => true,
                  'notnull' => true,
              ]);
              $table->addColumn('vvwork_group_id', 'integer', [
                  'notnull' => true,
              ]);
              $table->addColumn('parent_vvwork_group_id', 'integer', [
                  'notnull' => false,
              ]);
              $table->setPrimaryKey(['id']);
          }


          if (!$schema->hasTable('virwork_auth_group_role')) {
              $table = $schema->createTable('virwork_auth_group_role');
              $table->addColumn('id', 'integer', [
                  'autoincrement' => true,
                  'notnull' => true,
              ]);
              $table->addColumn('role', 'string', [
                  'notnull' => true,
                  'length' => 64,
              ]);
              $table->addColumn('vvwork_role_id', 'integer', [
                  'notnull' => true,
              ]);
              $table->addColumn('vvwork_group_id', 'integer', [
                  'notnull' => false,
              ]);
              $table->setPrimaryKey(['id']);
          }


          if (!$schema->hasTable('virwork_auth_user_role')) {
              $table = $schema->createTable('virwork_auth_user_role');
              $table->addColumn('id', 'integer', [
                  'autoincrement' => true,
                  'notnull' => true,
              ]);
              $table->addColumn('username', 'string', [
                  'notnull' => true,
                  'length' => 64,
              ]);
              $table->addColumn('vvwork_user_id', 'integer', [
                  'notnull' => true,
              ]);
              $table->addColumn('vvwork_role_id', 'integer', [
                  'notnull' => false,
              ]);
              $table->setPrimaryKey(['id']);
          }


          return $schema;
      }
  }