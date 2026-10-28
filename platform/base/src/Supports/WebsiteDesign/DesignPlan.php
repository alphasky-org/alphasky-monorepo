<?php

namespace Alphasky\Base\Supports\WebsiteDesign;

use RuntimeException;

/** A plan is data only. Approval never executes code or charges a wallet. */
final class DesignPlan
{
    public static function draft(string $siteId, string $ownerId): array
    {
        if ($siteId === '' || $ownerId === '') {
            throw new RuntimeException('design_identity_required');
        }
        return ['schema_version' => 1, 'site_id' => $siteId, 'owner_id' => $ownerId,
            'revision' => 0, 'status' => 'draft', 'brief' => [], 'steps' => [], 'approval' => null];
    }

    public static function revise(array $plan, string $ownerId, int $revision, array $brief, array $steps): array
    {
        self::guard($plan, $ownerId, $revision);
        if (!in_array($plan['status'], ['draft', 'review', 'approved'], true)) {
            throw new RuntimeException('design_already_started');
        }
        if (count($steps) < 1 || count($steps) > 30 || strlen(json_encode([$brief, $steps], JSON_THROW_ON_ERROR)) > 100000) {
            throw new RuntimeException('invalid_design_plan');
        }
        $seen = [];
        $validated = [];
        foreach ($steps as $step) {
            $id = $step['id'] ?? '';
            if (!is_string($id) || !preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $id) || isset($seen[$id])
                || !is_string($step['title'] ?? null) || trim($step['title']) === '' || strlen($step['title']) > 500
                || !is_string($step['requirements'] ?? null) || trim($step['requirements']) === '' || strlen($step['requirements']) > 12000
                || !is_array($step['depends_on'] ?? null)) {
                throw new RuntimeException('invalid_design_step');
            }
            foreach ($step['depends_on'] as $dependency) {
                if (!is_string($dependency) || !isset($seen[$dependency])) {
                    throw new RuntimeException('invalid_design_dependency');
                }
            }
            $seen[$id] = true;
            $validated[] = ['id' => $id, 'title' => $step['title'], 'requirements' => $step['requirements'],
                'depends_on' => array_values(array_unique($step['depends_on'])), 'status' => 'pending'];
        }
        $plan['brief'] = $brief;
        $plan['steps'] = $validated;
        $plan['revision']++;
        $plan['status'] = 'review';
        $plan['approval'] = null;
        return $plan;
    }

    public static function approve(array $plan, string $ownerId, int $revision): array
    {
        self::guard($plan, $ownerId, $revision);
        if (!in_array($plan['status'], ['review', 'approved'], true) || !$plan['steps']) {
            throw new RuntimeException('design_not_reviewable');
        }
        $plan['approval'] = ['owner_id' => $ownerId, 'revision' => $revision, 'hash' => self::fingerprint($plan)];
        $plan['status'] = 'approved';
        return $plan;
    }

    public static function assertExecutable(array $plan, string $siteId, string $currentOwnerId): void
    {
        self::guard($plan, $currentOwnerId, (int) $plan['revision']);
        $approval = $plan['approval'] ?? [];
        if ($plan['site_id'] !== $siteId || $plan['status'] !== 'approved'
            || ($approval['owner_id'] ?? null) !== $currentOwnerId
            || ($approval['revision'] ?? null) !== $plan['revision']
            || !hash_equals(self::fingerprint($plan), (string) ($approval['hash'] ?? ''))) {
            throw new RuntimeException('design_approval_required');
        }
    }

    private static function guard(array $plan, string $ownerId, int $revision): void
    {
        if (($plan['owner_id'] ?? null) !== $ownerId) {
            throw new RuntimeException('design_access_denied');
        }
        if (($plan['revision'] ?? null) !== $revision) {
            throw new RuntimeException('design_revision_conflict');
        }
    }

    private static function fingerprint(array $plan): string
    {
        return hash('sha256', json_encode([$plan['schema_version'], $plan['site_id'], $plan['owner_id'], $plan['revision'], $plan['brief'], $plan['steps']], JSON_THROW_ON_ERROR));
    }
}
