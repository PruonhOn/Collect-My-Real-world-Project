from django.urls import path
from . import views

urlpatterns = [

    # On Menu URLs
    path('Shop/', views.Shop, name='Shop'),

    # General Pages
    path('', views.IndexOgani, name='index'),
    path('AboutUs/', views.AboutUs, name='AboutUs'),
    path('bakery/', views.Bakery, name='bakery'),
    path('blog/', views.Blogs, name='blog'),
    path('blog-details/<int:blog_id>/', views.BlogDetails, name='blog-details'),
    path('bottled-beverages/', views.BottledBeverages, name='bottled-beverages'),
    path('breakfast/', views.Breakfast, name='breakfast'),
    path('checkout/', views.Checkout, name='checkout'),
    path('cold-coffee/', views.ColdCoffee, name='cold-coffee'),
    path('cold-tea/', views.ColdTea, name='cold-tea'),
    path('ContactUs/', views.ContactUs, name='Contact-Us'),
    path('customer/', views.customer, name='customer'),
    path('Frappuccino/', views.Frappuccino, name='Frappuccino'),
    path('hi/', views.hiSPI, name='hiSPI'),
    path('home/', views.home, name='home'),
    path('hot-chocolate/', views.HotChocolate, name='hot-chocolate'),
    path('hot-coffee/', views.HotCoffee, name='hot-coffee'),
    path('hot-tea/', views.HotTea, name='hot-tea'),
    path('lite-bites/', views.LiteBites, name='lite-bites'),
    path('Login/', views.Login, name='Login'),
    path('lunch/', views.Lunch, name='lunch'),
    path('Product/', views.Product, name='Product'),
    path('product-detail/<int:product_id>/', views.ProductDetail, name='product-detail'),
    path('shop-details/<int:product_id>/', views.ShopDetails, name='shop-details'),
    path('products/', views.products, name='products'),
    path('Promotion/', views.Promotion, name='Promotion'),
    path('protein-beverages/', views.ProteinBeverages, name='protein-beverages'),
    path('Refreshers/', views.Refreshers, name='Refreshers'),
    path('shopping-bag/', views.ShoppingBag, name='shopping-bag'),
    path('starbucks-via/', views.StarbucksVIA, name='starbucks-via'),
    path('treats/', views.Treats, name='treats'),
    path('trending/', views.Trending, name='trending'),
    path('whole-bean/', views.WholeBean, name='whole-bean'),

    
    # Cart URLs
    path('cart/', views.view_cart, name='view-cart'),
    path('cart/add/<int:product_id>/', views.add_to_cart, name='add-to-cart'),
    path('cart/remove/<int:item_id>/', views.remove_from_cart, name='remove-from-cart'),
    path('cart/update/<int:item_id>/', views.update_cart_item, name='update-cart-item'),
    path('cart/clear/', views.clear_cart, name='clear-cart'),

    # Wishlist URLs
    path('wishlist/', views.view_wishlist, name='view-wishlist'),
    path('wishlist/add/<int:product_id>/', views.add_to_wishlist, name='add-to-wishlist'),
    path('wishlist/remove/<int:item_id>/', views.remove_from_wishlist, name='remove-from-wishlist'),
    path('wishlist/clear/', views.clear_wishlist, name='clear-wishlist'),

    # Order URLs
    path('order/<str:order_number>/', views.order_invoice, name='order-invoice'),
    path('bill/<str:order_number>/', views.order_bill, name='order-bill'),
]