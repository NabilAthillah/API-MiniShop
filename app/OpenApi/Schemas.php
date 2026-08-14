<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Role',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Customer'),
    ]
)]
#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
        new OA\Property(property: 'phone', type: 'string', example: '081234567890'),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'role', ref: '#/components/schemas/Role', type: 'object'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'ProductImage',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'path', type: 'string', example: 'https://picsum.photos/seed/abc/600/600'),
        new OA\Property(property: 'alt', type: 'string', nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 0),
    ]
)]
#[OA\Schema(
    schema: 'Category',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Electronics'),
        new OA\Property(property: 'slug', type: 'string', example: 'electronics'),
        new OA\Property(
            property: 'products',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Product'),
            description: 'Only present when requested via ?with=products'
        ),
    ]
)]
#[OA\Schema(
    schema: 'Product',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Wireless Mouse'),
        new OA\Property(property: 'slug', type: 'string', example: 'wireless-mouse'),
        new OA\Property(property: 'price', type: 'integer', example: 150000),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'stock', type: 'integer', example: 25),
        new OA\Property(property: 'category', ref: '#/components/schemas/Category', type: 'object'),
        new OA\Property(
            property: 'images',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ProductImage')
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Address',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'label', type: 'string', example: 'Home'),
        new OA\Property(property: 'full_address', type: 'string', example: 'Jl. Merdeka No. 1, Jakarta'),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Near the red gate'),
        new OA\Property(property: 'recipient_name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'recipient_phone_number', type: 'string', example: '081234567890'),
        new OA\Property(property: 'is_primary', type: 'boolean', example: true),
        new OA\Property(property: 'user', ref: '#/components/schemas/User', type: 'object'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'TransactionProduct',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'qty', type: 'integer', example: 2),
        new OA\Property(property: 'product', ref: '#/components/schemas/Product', type: 'object'),
    ]
)]
#[OA\Schema(
    schema: 'Transaction',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User', type: 'object'),
        new OA\Property(property: 'address', ref: '#/components/schemas/Address', type: 'object'),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/TransactionProduct')
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Banner',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'image', type: 'string', example: 'http://localhost:8000/storage/banners/abc.jpg'),
        new OA\Property(property: 'alt', type: 'string', nullable: true, example: 'Summer sale banner'),
        new OA\Property(property: 'url', type: 'string', nullable: true, example: '/products/wireless-mouse'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Schemas
{
    //
}
