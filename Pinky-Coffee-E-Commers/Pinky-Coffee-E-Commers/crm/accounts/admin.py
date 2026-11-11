from django.contrib import admin
from .models import *  # Replace ... with your model names.
from django.utils.html import format_html

# Register your models here.

admin.site.site_header = "Saint Paul Instiute"
admin.site.index_title = "Admin Portal"
admin.site.site_title = "Saint Paul Instiute Admin Panal"
admin.site.register(Customer)  # Replace ... with your model names to register them in the admin site.
admin.site.register(Category)

class ProductsAdmin(admin.ModelAdmin):
    def product_preview(self, object):
        if object.productImage:
            return format_html('<img src="/static{}" style="width: 100px; height: auto"/>', object.productImage.url)
        return "No Image"
    product_preview.short_description = 'Product Image'
    list_display = ['product_preview', 'productName', 'categoryID', 'quantity', 'price', 'productDate']
    search_fields = ['productName', 'categoryID__categoryName']
    list_filter = ['productDate']
    list_per_page = 4
    readonly_fields = ['product_preview']


class ImageAdmin(admin.ModelAdmin):
    def image_preview(self, object):
        if object.ImageURL:
            return format_html('<img src="/static{}" style="width: 100px; height: auto"/>', object.ImageURL.url)
        return "No Image"
    image_preview.short_description = 'Image Preview'
    list_display = ['image_preview', 'ImageName', 'ImageTypeID', 'Active', 'ImageDate']
    search_fields = ['ImageName', 'ImageTypeID__ImageTypeName']
    list_filter = ['ImageDate']
    list_per_page = 4
    readonly_fields = ['image_preview']

admin.site.register(Products, ProductsAdmin)

admin.site.register(Image, ImageAdmin)
admin.site.register(ImageType)


class BlogAdmin(admin.ModelAdmin):
    def blog_preview(self, object):
        if object.image:
            return format_html('<img src="/static{}" style="width: 100px; height: auto"/>', object.image.url)
        return "No Image"
    blog_preview.short_description = 'Blog Image'
    list_display = ['blog_preview', 'title', 'author', 'category', 'created_at']
    search_fields = ['title', 'author', 'content', 'tags']
    list_filter = ['created_at', 'category']
    list_per_page = 10
    readonly_fields = ['blog_preview']

admin.site.register(Blog, BlogAdmin)


class SaleOffAdmin(admin.ModelAdmin):
    def product_preview(self, object):
        if object.product.productImage:
            return format_html('<img src="/static{}" style="width: 100px; height: auto"/>', object.product.productImage.url)
        return "No Image"
    product_preview.short_description = 'Product Image'

    def product_name(self, object):
        return object.product.productName
    product_name.short_description = 'Product'

    def category_name(self, object):
        return object.product.categoryID.categoryName
    category_name.short_description = 'Category'

    list_display = ['product_preview', 'product_name', 'category_name', 'discount_percentage', 'original_price', 'sale_price', 'is_active', 'created_at']
    search_fields = ['product__productName', 'product__categoryID__categoryName']
    list_filter = ['is_active', 'created_at', 'discount_percentage']
    list_editable = ['is_active', 'discount_percentage', 'original_price', 'sale_price']
    list_per_page = 10
    readonly_fields = ['product_preview']

    fieldsets = (
        ('Product Information', {
            'fields': ('product', 'product_preview')
        }),
        ('Discount Details', {
            'fields': ('discount_percentage', 'original_price', 'sale_price')
        }),
        ('Status', {
            'fields': ('is_active',)
        }),
    )

admin.site.register(SaleOff, SaleOffAdmin)


class OrderAdmin(admin.ModelAdmin):
    list_display = ['order_number', 'get_full_name', 'order_status', 'total', 'created_at']
    search_fields = ['order_number', 'first_name', 'last_name', 'email']
    list_filter = ['order_status', 'created_at']
    list_per_page = 10
    readonly_fields = ['order_number', 'created_at', 'updated_at']

    def get_full_name(self, obj):
        return obj.get_full_name()
    get_full_name.short_description = 'Customer'

admin.site.register(Order, OrderAdmin)

class OrderItemAdmin(admin.ModelAdmin):
    list_display = ['order', 'product', 'quantity', 'price']
    search_fields = ['order__order_number', 'product__productName']
    list_filter = ['order__created_at']
    list_per_page = 10
admin.site.register(OrderItem, OrderItemAdmin)


    