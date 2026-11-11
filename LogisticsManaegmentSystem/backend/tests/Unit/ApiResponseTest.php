<?php

namespace Tests\Unit;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * The envelope is the contract every client depends on, so its shape is
 * asserted here rather than trusted.
 */
class ApiResponseTest extends TestCase
{
    public function test_success_wraps_data_with_message_and_flag(): void
    {
        $response = ApiResponse::success(['id' => 1], 'Saved.');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Saved.',
            'data' => ['id' => 1],
        ], $response->getData(true));
    }

    public function test_success_omits_meta_when_empty(): void
    {
        $payload = ApiResponse::success(['id' => 1])->getData(true);

        $this->assertArrayNotHasKey('meta', $payload);
    }

    public function test_success_includes_meta_when_supplied(): void
    {
        $payload = ApiResponse::success(null, 'OK', 200, ['page' => 1])->getData(true);

        $this->assertSame(['page' => 1], $payload['meta']);
    }

    public function test_created_returns_201_with_location_header(): void
    {
        $response = ApiResponse::created(['id' => 9], 'Shipment created.', '/api/v1/shipments/9');

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('/api/v1/shipments/9', $response->headers->get('Location'));
    }

    public function test_error_serialises_empty_errors_as_an_object_not_a_list(): void
    {
        $response = ApiResponse::error('Something failed.', 400);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);

        // A JSON array would force the client to handle two shapes. Assert on
        // the raw body because json_decode(assoc) erases the distinction.
        $this->assertStringContainsString('"errors":{}', (string) $response->getContent());
    }

    public function test_error_passes_field_messages_through(): void
    {
        $response = ApiResponse::error('Invalid.', 422, [
            'email' => ['The email field is required.'],
        ]);

        $this->assertSame(
            ['email' => ['The email field is required.']],
            $response->getData(true)['errors'],
        );
    }

    public function test_named_status_helpers_use_the_right_codes(): void
    {
        $this->assertSame(404, ApiResponse::notFound()->getStatusCode());
        $this->assertSame(403, ApiResponse::forbidden()->getStatusCode());
        $this->assertSame(409, ApiResponse::conflict('Duplicate.')->getStatusCode());
    }

    public function test_paginated_produces_normalised_meta(): void
    {
        $paginator = new LengthAwarePaginator(
            collect(array_map(fn (int $id) => (object) ['id' => $id], [1, 2, 3])),
            total: 57,
            perPage: 25,
            currentPage: 1,
        );

        $payload = ApiResponse::paginated($paginator, 'Shipments retrieved.')->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertCount(3, $payload['data']);
        $this->assertSame([
            'current_page' => 1,
            'per_page' => 25,
            'total' => 57,
            'last_page' => 3,
            'from' => 1,
            'to' => 3,
            'has_more_pages' => true,
        ], $payload['meta']);
    }

    public function test_paginated_meta_reports_an_empty_result_set_without_fake_offsets(): void
    {
        $paginator = new LengthAwarePaginator(collect(), total: 0, perPage: 25, currentPage: 1);

        $meta = ApiResponse::paginated($paginator)->getData(true)['meta'];

        // firstItem()/lastItem() must be null here, not 0, otherwise a client
        // renders "rows 1-0 of 0".
        $this->assertNull($meta['from']);
        $this->assertNull($meta['to']);
        $this->assertSame(0, $meta['total']);
        $this->assertFalse($meta['has_more_pages']);
    }
}
