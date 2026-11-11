from multiprocessing import context
from django.shortcuts import render, get_object_or_404, redirect
from django.http import HttpResponse, JsonResponse
from django.contrib import messages
from django.utils import timezone
from django.utils.crypto import get_random_string
from decimal import Decimal
from django.core.paginator import Paginator
from django.db import models
from .models import Category, Products, ImageType, Image, Cart, CartItem, Wishlist, WishlistItem, Order, OrderItem, Blog, SaleOff


def products(request):
    return HttpResponse('Products_page')

def customer (request):
    return HttpResponse('Customer_page')

def hiSPI (request):
    return HttpResponse('Hi SPI')

def home(request):
    DTCategory = Category.objects.all()
    DTProducts = Products.objects.all()

    SPI = "Saint Paul Institute"
    UnitPrice = 5000
    Quantity = 10
    TotalPrice = UnitPrice * Quantity
    Days = ['ចន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍', 'អាទិត្យ']
    Months = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ']
    Years = ['2020', '2021', '2022', '2023', '2024']
    
    DTBanner = Image.objects.get(ImageTypeID=1)
    DTSlideShow = Image.objects.filter(ImageTypeID=2)
    DTSlidebarLeft = Image.objects.filter(ImageTypeID=3)
    DTSlidebarRight = Image.objects.filter(ImageTypeID=4)
    DTFooter = Image.objects.filter(ImageTypeID=5)
    DTLogo = Image.objects.get(ImageTypeID=6)
    context = {
        'objects': SPI,
        'ObjPrice': TotalPrice,
        'ObjDays': Days,
        'ObjMonths': Months,
        'ObjYears': Years,
        'UnitPrice': UnitPrice,
        'Quantity': Quantity,
        'ObjDTCategory': DTCategory,
        'ObjDTProducts': DTProducts,
        'ObjDTBanner': DTBanner,
        'ObjDTSlideShow': DTSlideShow,
        'ObjDTSlidebarLeft': DTSlidebarLeft,
        'ObjDTSlidebarRight': DTSlidebarRight,
        'ObjDTFooter': DTFooter,
        'ObjDTLogo': DTLogo,
    }
    return render(request, 'accounts/index.html', context)

def AboutUs(request):

    DTExclude = Image.objects.exclude(id=10)
    DTUseOrderBy = Image.objects.order_by('-id')
    DTUseFirst = Image.objects.first()
    DTUseLast = Image.objects.last()
    DTUseCount = Image.objects.count()
    DTUseExists = Image.objects.filter(ImageTypeID=2).exists()

    context = {
        'ObjDTExclude': DTExclude,
        'ObjDTUseOrderBy': DTUseOrderBy,
        'ObjDTUseFirst': DTUseFirst,
        'ObjDTUseLast': DTUseLast,
        'ObjDTUseCount': DTUseCount,
        'ObjDTUseExists': DTUseExists,
    }
    return render(request, 'accounts/AboutUs.html', context)

def ContactUs(request):
    return render(request, 'accounts/ContactUs.html')

def Login(request):
    return render(request, 'accounts/Login.html')

def Promotion(request):
    return render(request, 'accounts/Promotion.html')

def Product(request):
    return render(request, 'accounts/Product.html')

def ProductDetail(request, product_id):
    """View for displaying product detail page"""
    product = get_object_or_404(Products, id=product_id)
    # Get related products from the same category
    related_products = Products.objects.filter(categoryID=product.categoryID).exclude(id=product_id)[:4]
    # Get all categories for sidebar
    categories = Category.objects.all()

    context = get_common_context(request)
    context.update({
        'product': product,
        'related_products': related_products,
        'categories': categories,
    })
    return render(request, 'Pinky-Caffee/product-detail.html', context)

def ShopDetails(request, product_id):
    """View for displaying shop-details page (dynamic version)"""
    product = get_object_or_404(Products, id=product_id)
    # Get related products from the same category
    related_products = Products.objects.filter(categoryID=product.categoryID).exclude(id=product_id)[:4]
    # Get all categories for sidebar
    categories = Category.objects.all()

    context = get_common_context(request)
    context.update({
        'product': product,
        'related_products': related_products,
        'categories': categories,
    })
    return render(request, 'Pinky-Caffee/shop-details.html', context)

def get_common_context(request=None):
    context = {
        'DTBannerLeft': Image.objects.get(ImageTypeID=8),
        'DTBannerRight': Image.objects.get(ImageTypeID=9),
        'DTTrending': Products.objects.filter(categoryID=4),
        'DTLatestLeft': Products.objects.all()[6:9],
        'DTLatestRight': Products.objects.all()[3:6],
        'DTProteinBeverages': Products.objects.filter(categoryID=5),
        'DTTopRated': Products.objects.all()[7:10],
        'DTReviewProducts': Products.objects.all()[3:6],
        'DTBlogs': Blog.objects.all()[:3],
    }

    # Add cart and wishlist counts if request is provided
    if request:
        cart = get_or_create_cart(request)
        wishlist = get_or_create_wishlist(request)
        context.update({
            'cart_total_items': cart.get_total_items(),
            'wishlist_total_items': wishlist.get_total_items(),
        })

    return context

def IndexOgani(request):
    context = get_common_context(request)
    return render(request, 'Pinky-Caffee/index.html', context)

def Trending(request):
    context = get_common_context(request)
    return render(request, 'Pinky-Caffee/trending.html', context)

def ProteinBeverages(request):
    context = get_common_context(request)
    return render(request, 'Pinky-Caffee/protein-beverages.html', context)

def HotCoffee(request):
    context = get_common_context(request)
    context['DTHotCoffee'] = Products.objects.filter(categoryID__categoryName='Hot Coffee')
    return render(request, 'Pinky-Caffee/hot-coffee.html', context)

def ColdCoffee(request):
    context = get_common_context(request)
    context['DTColdCoffee'] = Products.objects.filter(categoryID__categoryName='Cold Coffee')
    return render(request, 'Pinky-Caffee/cold-coffee.html', context)

def HotTea(request):
    context = get_common_context(request)
    context['DTHotTea'] = Products.objects.filter(categoryID__categoryName='Hot Tea')
    return render(request, 'Pinky-Caffee/hot-tea.html', context)

def ColdTea(request):
    context = get_common_context(request)
    context['DTColdTea'] = Products.objects.filter(categoryID__categoryName='Cold Tea')
    return render(request, 'Pinky-Caffee/cold-tea.html', context)

def Refreshers(request):
    context = get_common_context(request)
    context['DTRefreshers'] = Products.objects.filter(categoryID__categoryName='Refreshers')
    return render(request, 'Pinky-Caffee/Refreshers.html', context)

def Frappuccino(request):
    context = get_common_context(request)
    context['DTFrappuccino'] = Products.objects.filter(categoryID__categoryName='Frappuccino')
    return render(request, 'Pinky-Caffee/Frappuccino.html', context)

def HotChocolate(request):
    context = get_common_context(request)
    context['DTHotChocolate'] = Products.objects.filter(categoryID__categoryName='Hot Chocolate')
    return render(request, 'Pinky-Caffee/hot-chocolate.html', context)

def BottledBeverages(request):
    context = get_common_context(request)
    context['DTBottledBeverages'] = Products.objects.filter(categoryID__categoryName='Bottled Beverages')
    return render(request, 'Pinky-Caffee/bottled-beverages.html', context)

def Breakfast(request):
    context = get_common_context(request)
    context['DTBreakfast'] = Products.objects.filter(categoryID__categoryName='Breakfast')
    return render(request, 'Pinky-Caffee/breakfast.html', context)

def Bakery(request):
    context = get_common_context(request)
    context['DTBakery'] = Products.objects.filter(categoryID__categoryName='Bakery')
    return render(request, 'Pinky-Caffee/bakery.html', context)

def Treats(request):
    context = get_common_context(request)
    context['DTTreats'] = Products.objects.filter(categoryID__categoryName='Treats')
    return render(request, 'Pinky-Caffee/treats.html', context)

def Lunch(request):
    context = get_common_context(request)
    context['DTLunch'] = Products.objects.filter(categoryID__categoryName='Lunch')
    return render(request, 'Pinky-Caffee/lunch.html', context)

def LiteBites(request):
    context = get_common_context(request)
    context['DTLiteBites'] = Products.objects.filter(categoryID__categoryName='Lite Bites')
    return render(request, 'Pinky-Caffee/lite-bites.html', context)

def WholeBean(request):
    context = get_common_context(request)
    context['DTWholeBean'] = Products.objects.filter(categoryID__categoryName='Whole Bean')
    return render(request, 'Pinky-Caffee/whole-bean.html', context)

def StarbucksVIA(request):
    context = get_common_context(request)
    context['DTStarbucksVIA'] = Products.objects.filter(categoryID__categoryName='Starbucks VIA')
    return render(request, 'Pinky-Caffee/Starbucks-VIA.html', context)

def ShoppingBag(request):
    context = get_common_context(request)
    context['DTShoppingBag'] = Products.objects.filter(categoryID__categoryName='Shopping Bag')
    return render(request, 'Pinky-Caffee/shopping-bag.html', context)


# ============== Menu FUNCTIONS ==============

def ContactUs(request):
    context = get_common_context(request)
    return render(request, 'Pinky-Caffee/Contact.html', context)

def Shop(request):
    context = get_common_context(request)
    DTCategory = Category.objects.all()

    # Handle category filtering
    category_filter = request.GET.get('category', '').strip()
    DTProducts = Products.objects.all()

    if category_filter:
        DTProducts = DTProducts.filter(categoryID__categoryName=category_filter)

    # Get latest products for sidebar (recent 3)
    latest_products = Products.objects.all().order_by('-productDate')[:3]

    # Get active sale off products
    sale_off_products = SaleOff.objects.filter(is_active=True).select_related('product').order_by('-created_at')[:6]

    # Pagination
    paginator = Paginator(DTProducts, 9)  # Show 9 products per page
    page_number = request.GET.get('page')
    page_obj = paginator.get_page(page_number)

    context.update({
        'ObjDTCategory': DTCategory,
        'ObjDTProducts': page_obj,
        'latest_products': latest_products,
        'sale_off_products': sale_off_products,
        'paginator': paginator,
        'page_obj': page_obj,
        'category_filter': category_filter,
    })
    return render(request, 'Pinky-Caffee/shop-grid.html', context)

def Blogs(request):
    context = get_common_context(request)

    # Handle search query and category filter
    search_query = request.GET.get('search', '').strip()
    category_filter = request.GET.get('category', '').strip()
    blogs = Blog.objects.all().order_by('-created_at')

    if search_query:
        # Search in title, content, and tags
        blogs = blogs.filter(
            models.Q(title__icontains=search_query) |
            models.Q(content__icontains=search_query) |
            models.Q(tags__icontains=search_query)
        )

    if category_filter:
        # Filter by category
        blogs = blogs.filter(category=category_filter)

    # Get unique categories for sidebar (from all blogs, not filtered)
    all_blogs = Blog.objects.all()
    categories = []
    for cat_key, cat_name in Blog.CATEGORY_CHOICES:
        if all_blogs.filter(category=cat_key).exists():
            # Build URL with preserved search query
            url = f'?category={cat_key}'
            if search_query:
                url = f'?search={search_query}&category={cat_key}'
            categories.append({
                'key': cat_key,
                'name': cat_name,
                'url': url
            })

    # Get unique tags for sidebar (from all blogs, not filtered)
    all_tags = set()
    for blog in all_blogs:
        all_tags.update(blog.get_tags_list())
    tags = []
    for tag in sorted(list(all_tags)):
        # Build URL with preserved search and category
        url = f'?search={tag}'
        if category_filter:
            url = f'?search={tag}&category={category_filter}'
        tags.append({
            'name': tag,
            'url': url
        })

    # Get recent blogs for sidebar (latest 3 from all blogs)
    recent_blogs = Blog.objects.all().order_by('-created_at')[:3]

    # Pagination
    paginator = Paginator(blogs, 6)  # Show 6 blogs per page
    page_number = request.GET.get('page')
    page_obj = paginator.get_page(page_number)

    context.update({
        'DTBlogs': page_obj,
        'DTBlogsRecent': recent_blogs,
        'blog_categories': categories,
        'blog_tags': tags,
        'paginator': paginator,
        'page_obj': page_obj,
        'search_query': search_query,
        'category_filter': category_filter,
    })
    return render(request, 'Pinky-Caffee/blog.html', context)

def BlogDetails(request, blog_id):
    blog = get_object_or_404(Blog, id=blog_id)

    # Get unique categories for sidebar (from all blogs)
    all_blogs = Blog.objects.all()
    categories = []
    for cat_key, cat_name in Blog.CATEGORY_CHOICES:
        if all_blogs.filter(category=cat_key).exists():
            categories.append({
                'key': cat_key,
                'name': cat_name,
                'url': f'?category={cat_key}'
            })

    # Get unique tags for sidebar (from all blogs)
    all_tags = set()
    for b in all_blogs:
        all_tags.update(b.get_tags_list())
    tags = []
    for tag in sorted(list(all_tags)):
        tags.append({
            'name': tag,
            'url': f'?search={tag}'
        })

    # Get recent blogs for sidebar (latest 3, excluding current)
    recent_blogs = Blog.objects.exclude(id=blog_id).order_by('-created_at')[:3]

    # Get related blogs (same category, excluding current)
    related_blogs = Blog.objects.filter(category=blog.category).exclude(id=blog_id).order_by('-created_at')[:3]

    context = get_common_context(request)
    context.update({
        'blog': blog,
        'DTBlogsRecent': recent_blogs,
        'blog_categories': categories,
        'blog_tags': tags,
        'related_blogs': related_blogs,
    })
    return render(request, 'Pinky-Caffee/blog-details.html', context)

def Checkout(request):
    cart = get_or_create_cart(request)
    cart_items = cart.cartitem_set.all()

    # Redirect to cart if cart is empty
    if not cart_items.exists():
        messages.info(request, 'Your cart is empty. Add some items before proceeding to checkout.')
        return redirect('view-cart')

    if request.method == 'POST':
        # Process the order
        try:
            # Generate unique order number
            order_number = f'ORD-{timezone.now().strftime("%Y%m%d")}-{get_random_string(6).upper()}'

            # Create the order
            order = Order.objects.create(
                order_number=order_number,
                session_key=request.session.session_key,

                # Customer info
                first_name=request.POST.get('first_name'),
                last_name=request.POST.get('last_name'),
                email=request.POST.get('email'),
                phone=request.POST.get('phone'),

                # Address
                street_address=request.POST.get('street_address'),
                apartment=request.POST.get('apartment', ''),
                city=request.POST.get('city'),
                state=request.POST.get('state'),
                postcode=request.POST.get('postcode'),
                country=request.POST.get('country'),

                # Order details
                subtotal=Decimal(str(cart.get_total_price())),
                shipping_cost=Decimal('2.00'),
                discount=Decimal('0.00'),
                total=Decimal(str(cart.get_total_price())) + Decimal('2.00'),

                # Payment and options
                payment_method=request.POST.get('payment_method'),
                order_notes=request.POST.get('order_notes', ''),
                create_account=request.POST.get('create_account') == 'on',
                different_shipping=request.POST.get('different_address') == 'on',
            )

            # Create order items
            for cart_item in cart_items:
                OrderItem.objects.create(
                    order=order,
                    product=cart_item.product,
                    quantity=cart_item.quantity,
                    price=Decimal(str(cart_item.product.price))
                )

            # Clear the cart after successful order
            cart.cartitem_set.all().delete()

            # Redirect to bill
            messages.success(request, f'Order {order_number} placed successfully!')
            return redirect('order-bill', order_number=order_number)

        except Exception as e:
            messages.error(request, f'Error processing order: {str(e)}')
            return redirect('checkout')

    context = get_common_context(request)
    context.update({
        'cart': cart,
        'cart_items': cart_items,
    })
    return render(request, 'Pinky-Caffee/checkout.html', context)


# ============== CART FUNCTIONS ==============

def get_or_create_cart(request):
    """Get existing cart or create new one based on session"""
    if not request.session.session_key:
        request.session.create()
    
    session_key = request.session.session_key
    cart, created = Cart.objects.get_or_create(session_key=session_key)
    return cart


def add_to_cart(request, product_id):
    """Add a product to the shopping cart"""
    product = get_object_or_404(Products, id=product_id)
    cart = get_or_create_cart(request)
    
    # Get quantity from request (default to 1)
    quantity = int(request.POST.get('quantity', 1)) if request.method == 'POST' else 1
    
    # Check if item already exists in cart
    cart_item, created = CartItem.objects.get_or_create(
        cart=cart,
        product=product,
        defaults={'quantity': quantity}
    )
    
    if not created:
        # Item already exists, update quantity
        cart_item.quantity += quantity
        cart_item.save()
        messages.success(request, f'Updated {product.productName} quantity in your cart.')
    else:
        messages.success(request, f'Added {product.productName} to your cart.')
    
    # Check if it's an AJAX request
    if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
        return JsonResponse({
            'success': True,
            'message': f'{product.productName} added to cart',
            'cart_total': cart.get_total_items(),
            'cart_price': cart.get_total_price()
        })
    
    # Redirect back to the referring page or product detail
    next_url = request.POST.get('next', request.META.get('HTTP_REFERER', '/'))
    return redirect(next_url)


def remove_from_cart(request, item_id):
    """Remove an item from the shopping cart"""
    cart = get_or_create_cart(request)
    cart_item = get_object_or_404(CartItem, id=item_id, cart=cart)
    product_name = cart_item.product.productName
    cart_item.delete()
    
    messages.success(request, f'Removed {product_name} from your cart.')
    
    if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
        return JsonResponse({
            'success': True,
            'message': f'{product_name} removed from cart',
            'cart_total': cart.get_total_items(),
            'cart_price': cart.get_total_price()
        })
    
    return redirect('view-cart')


def update_cart_item(request, item_id):
    """Update quantity of an item in the cart"""
    if request.method == 'POST':
        cart = get_or_create_cart(request)
        cart_item = get_object_or_404(CartItem, id=item_id, cart=cart)
        
        quantity = int(request.POST.get('quantity', 1))
        
        if quantity > 0:
            cart_item.quantity = quantity
            cart_item.save()
            messages.success(request, f'Updated {cart_item.product.productName} quantity.')
        else:
            cart_item.delete()
            messages.success(request, f'Removed {cart_item.product.productName} from cart.')
        
        if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
            return JsonResponse({
                'success': True,
                'cart_total': cart.get_total_items(),
                'cart_price': cart.get_total_price(),
                'item_subtotal': cart_item.get_subtotal() if quantity > 0 else 0
            })
    
    return redirect('view-cart')


def view_cart(request):
    """Display the shopping cart page"""
    cart = get_or_create_cart(request)
    cart_items = cart.cartitem_set.select_related('product').all()

    context = get_common_context(request)
    context.update({
        'cart': cart,
        'cart_items': cart_items,
    })
    return render(request, 'Pinky-Caffee/cart.html', context)


def clear_cart(request):
    """Clear all items from the cart"""
    cart = get_or_create_cart(request)
    cart.cartitem_set.all().delete()
    messages.success(request, 'Your cart has been cleared.')

    if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
        return JsonResponse({'success': True, 'message': 'Cart cleared'})

    return redirect('view-cart')


# ============== WISHLIST FUNCTIONS ==============

def get_or_create_wishlist(request):
    """Get existing wishlist or create new one based on session"""
    if not request.session.session_key:
        request.session.create()

    session_key = request.session.session_key
    wishlist, created = Wishlist.objects.get_or_create(session_key=session_key)
    return wishlist


def add_to_wishlist(request, product_id):
    """Add a product to the wishlist"""
    product = get_object_or_404(Products, id=product_id)
    wishlist = get_or_create_wishlist(request)

    # Check if item already exists in wishlist
    wishlist_item, created = WishlistItem.objects.get_or_create(
        wishlist=wishlist,
        product=product,
        defaults={'added_at': timezone.now()}
    )

    if not created:
        messages.info(request, f'{product.productName} is already in your wishlist.')
    else:
        messages.success(request, f'Added {product.productName} to your wishlist.')

    # Check if it's an AJAX request
    if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
        return JsonResponse({
            'success': True,
            'message': f'{product.productName} added to wishlist',
            'wishlist_total': wishlist.get_total_items()
        })

    # Redirect back to the referring page or product detail
    next_url = request.POST.get('next', request.META.get('HTTP_REFERER', '/'))
    return redirect(next_url)


def remove_from_wishlist(request, item_id):
    """Remove an item from the wishlist"""
    wishlist = get_or_create_wishlist(request)
    wishlist_item = get_object_or_404(WishlistItem, id=item_id, wishlist=wishlist)
    product_name = wishlist_item.product.productName
    wishlist_item.delete()

    messages.success(request, f'Removed {product_name} from your wishlist.')

    if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
        return JsonResponse({
            'success': True,
            'message': f'{product_name} removed from wishlist',
            'wishlist_total': wishlist.get_total_items()
        })

    return redirect('view-wishlist')


def order_invoice(request, order_number):
    """Display order invoice"""
    try:
        order = Order.objects.get(order_number=order_number)
        order_items = order.orderitem_set.select_related('product').all()

        context = get_common_context(request)
        context.update({
            'order': order,
            'order_items': order_items,
        })
        return render(request, 'Pinky-Caffee/invoice.html', context)
    except Order.DoesNotExist:
        messages.error(request, 'Order not found.')
        return redirect('index')


def order_bill(request, order_number):
    """Display order bill/receipt"""
    try:
        order = Order.objects.get(order_number=order_number)
        order_items = order.orderitem_set.select_related('product').all()

        context = get_common_context(request)
        context.update({
            'order': order,
            'order_items': order_items,
        })
        return render(request, 'Pinky-Caffee/bill.html', context)
    except Order.DoesNotExist:
        messages.error(request, 'Order not found.')
        return redirect('index')


def view_wishlist(request):
    """Display the wishlist page"""
    wishlist = get_or_create_wishlist(request)
    wishlist_items = wishlist.wishlistitem_set.select_related('product').all()

    context = get_common_context(request)
    context.update({
        'wishlist': wishlist,
        'wishlist_items': wishlist_items,
    })
    return render(request, 'Pinky-Caffee/wishlist.html', context)


def clear_wishlist(request):
    """Clear all items from the wishlist"""
    wishlist = get_or_create_wishlist(request)
    wishlist.wishlistitem_set.all().delete()
    messages.success(request, 'Your wishlist has been cleared.')

    if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
        return JsonResponse({'success': True, 'message': 'Wishlist cleared'})

    return redirect('view-wishlist')