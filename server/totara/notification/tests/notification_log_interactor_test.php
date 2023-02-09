<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\interactor\notification_audit_interactor;
use totara_notification\testing\generator;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;
use totara_notification_mock_scheduled_aware_event_resolver as mock_schedule_resolver;

/**
 * @group totara_notification
 */
class totara_notification_notification_log_interactor_testcase extends testcase {
    /**
     * @var stdClass
     */
    private $user_one;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();
        $this->user_one = $generator->create_user();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->user_one = null;
    }

    /**
     * @return void
     */
    public function test_check_cannot_audit_without_capability_and_no_permission_granted_by_resolver(): void {
        $generator = generator::instance();
        $generator->include_mock_scheduled_aware_notifiable_event_resolver();

        $interactor = new notification_audit_interactor(
            extended_context::make_system(),
            $this->user_one->id
        );

        self::assertFalse($interactor->can_audit_notifications());
        self::assertFalse($interactor->can_audit_notifications_of_resolver(mock_schedule_resolver::class));
    }

    /**
     * @return void
     */
    public function test_check_can_audit_with_capability_and_no_permission_granted_by_the_resolver(): void {
        global $DB;
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        $role_id = $DB->get_field(
            'role',
            'id',
            ['shortname' => 'user']
        );

        $extended_context = extended_context::make_system();
        assign_capability(
            'totara/notification:auditnotifications',
            CAP_ALLOW,
            $role_id,
            $extended_context->get_context_id(),
            true
        );

        $interactor = new notification_audit_interactor(
            $extended_context,
            $this->user_one->id
        );

        self::assertTrue($interactor->can_audit_notifications());
        self::assertTrue($interactor->can_audit_notifications_of_resolver(mock_resolver::class));
    }

    /**
     * @return void
     */
    public function test_check_can_audit_with_own_capability_and_no_permission_granted_by_the_resolver(): void {
        global $DB;
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        self::setUser($this->user_one->id);

        $role_id = $DB->get_field(
            'role',
            'id',
            ['shortname' => 'user']
        );

        $system_context = context_system::instance();

        assign_capability(
            'totara/notification:auditownnotifications',
            CAP_ALLOW,
            $role_id,
            $system_context->id,
            true
        );

        $interactor = new notification_audit_interactor(
            extended_context::make_system(),
            $this->user_one->id,
            $this->user_one->id
        );

        self::assertTrue($interactor->can_audit_notifications());
        self::assertTrue($interactor->can_audit_notifications_of_resolver(mock_resolver::class));
    }

    /**
     * @return void
     */
    public function test_check_can_audit_without_capability_but_has_permissions_at_resolver(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        $ec = extended_context::make_system();
        mock_resolver::set_permissions($ec, $this->user_one->id, true);

        $interactor = new notification_audit_interactor(
            $ec,
            $this->user_one->id
        );

        self::assertFalse($interactor->can_audit_notifications());
        self::assertTrue($interactor->can_audit_notifications_of_resolver(mock_resolver::class));
    }

    /**
     * @return void
     */
    public function test_check_cannot_audit_without_capability_and_no_permissions_at_resolver(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        $ec = extended_context::make_system();
        mock_resolver::set_permissions($ec, $this->user_one->id, false);

        $interactor = new notification_audit_interactor(
            $ec,
            $this->user_one->id
        );

        self::assertFalse($interactor->can_audit_notifications());
        self::assertFalse($interactor->can_audit_notifications_of_resolver(mock_resolver::class));
    }

    /**
     * @return void
     */
    public function test_check_can_audit_with_own_capability_on_tenants(): void {
        global $DB;

        $generator = $this->getDataGenerator();

        // Enable tenants.
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        // Create tenants.
        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        // Create tenant user.
        $tenant_user1 = $generator->create_user(
            ['tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber]
        );
        $tenant_user2 = $generator->create_user(
            ['tenantid' => $tenant_two->id, 'tenantdomainmanager' => $tenant_two->idnumber]
        );

        $context_tenant_one = context_tenant::instance($tenant_one->id);
        $context_tenant_two = context_tenant::instance($tenant_two->id);
        $extended_context_tenant_one = extended_context::make_with_context($context_tenant_one);
        $extended_context_tenant_two = extended_context::make_with_context($context_tenant_two);

        $role_user_id = $DB->get_field(
            'role',
            'id',
            ['shortname' => 'user']
        );

        $role_manager_id = $DB->get_field(
            'role',
            'id',
            ['shortname' => 'manager']
        );

        // Remove default capability
        unassign_capability(
            'totara/notification:auditnotifications',
            $role_manager_id
        );

        unassign_capability(
            'totara/notification:auditownnotifications',
            $role_user_id
        );

        $interactor_tenant_one = new notification_audit_interactor(
            $extended_context_tenant_one,
            $tenant_user1->id,
            $tenant_user1->id
        );

        $interactor_tenant_two = new notification_audit_interactor(
            $extended_context_tenant_two,
            $tenant_user2->id,
            $tenant_user2->id
        );

        // No capability assigned.
        self::assertFalse($interactor_tenant_two->can_audit_notifications());
        self::assertFalse($interactor_tenant_one->can_audit_notifications());

        // Create a new role
        $new_role_id = create_role('own_audit_role', 'oar', '');
        role_assign($new_role_id, $tenant_user1->id, $context_tenant_one->id);

        // Assign capability to the role in tenant one.
        assign_capability(
            'totara/notification:auditownnotifications',
            CAP_ALLOW,
            $new_role_id,
            $context_tenant_one
        );

        // Only the tenant_one user can audit the notification log.
        self::assertTrue($interactor_tenant_one->can_audit_notifications());
        self::assertFalse($interactor_tenant_two->can_audit_notifications());
    }
}
