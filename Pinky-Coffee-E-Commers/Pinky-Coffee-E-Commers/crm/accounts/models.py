from django.db import models

# Create your models here.
class Customer(models.Model):
    name = models.CharField(max_length=200, null=True)
    phone = models.CharField(max_length=200, null=True)
    email = models.EmailField(max_length=200, null=True)
    data_created = models.DateTimeField(auto_now_add=True, null=True)

    def __str__(self):
        return f'{self.id} - {self.name} - {self.phone} - {self.email}'
    


class Category(models.Model):
    categoryName = models.CharField(max_length=100)
    def __str__(self):
        return f'{self.id} - {self.categoryName}'
    

class Products(models.Model):
    productName = models.CharField(max_length=200, null=True)
    categoryID = models. ForeignKey(Category, on_delete=models. CASCADE, null=True)
    quantity = models.CharField(max_length=200, null=True)
    price = models.CharField(max_length=200, null=True)
    productImage = models. ImageField(upload_to='images/product',null=True,blank=True)
    productDate = models.DateTimeField(auto_now_add=True, null=True)
    def __str__(self):
        return self.productName
    
class ImageType(models.Model): 
    ImageTypeName = models.CharField(max_length=200, null=True) 
    ImageTypeDate = models.DateTimeField(auto_now_add=True, null=True) 
    def __str__(self):          
        return f'{self.id} - {self.ImageTypeName}'
    
class Image(models.Model): 
    ImageName = models.CharField(max_length=200, null=True) 
    ImageURL = models.ImageField(upload_to='images/',null=True,blank=True) 
    ImageLink = models.CharField(max_length=200, null=True) 
    ImageTypeID = models.ForeignKey(ImageType, on_delete=models.CASCADE, null=True) 
    Active = models.CharField(max_length=200, null=True) 
    ImageDate = models.DateTimeField(auto_now_add=True, null=True) 
    def __str__(self):          
        return f'{self.id} - {self.ImageTypeID} - {self.ImageTypeID.ImageTypeName}'


class Cart(models.Model):
    """Shopping cart model - uses session to track cart items"""
    session_key = models.CharField(max_length=255)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    def __str__(self):
        return f'Cart {self.id} - Session: {self.session_key[:20]}...'
    
    def get_total_price(self):
        """Calculate total price of all items in cart"""
        total = 0
        for item in self.cartitem_set.all():
            try:
                total += float(item.product.price) * item.quantity
            except (ValueError, TypeError):
                pass
        return total
    
    def get_total_items(self):
        """Get total number of items in cart"""
        return sum(item.quantity for item in self.cartitem_set.all())


class CartItem(models.Model):
    """Individual item in a shopping cart"""
    cart = models.ForeignKey(Cart, on_delete=models.CASCADE)
    product = models.ForeignKey(Products, on_delete=models.CASCADE)
    quantity = models.PositiveIntegerField(default=1)
    added_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ('cart', 'product')

    def __str__(self):
        return f'{self.quantity}x {self.product.productName}'

    def get_subtotal(self):
        """Calculate subtotal for this item"""
        try:
            return float(self.product.price) * self.quantity
        except (ValueError, TypeError):
            return 0


class Wishlist(models.Model):
    """Wishlist model - uses session to track wishlist items"""
    session_key = models.CharField(max_length=255)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f'Wishlist {self.id} - Session: {self.session_key[:20]}...'

    def get_total_items(self):
        """Get total number of items in wishlist"""
        return self.wishlistitem_set.count()


class WishlistItem(models.Model):
    """Individual item in a wishlist"""
    wishlist = models.ForeignKey(Wishlist, on_delete=models.CASCADE)
    product = models.ForeignKey(Products, on_delete=models.CASCADE)
    added_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ('wishlist', 'product')

    def __str__(self):
        return f'{self.product.productName}'


class Order(models.Model):
    """Order model for completed purchases"""
    order_number = models.CharField(max_length=20, unique=True)
    session_key = models.CharField(max_length=255)

    # Customer Information
    first_name = models.CharField(max_length=50)
    last_name = models.CharField(max_length=50)
    email = models.EmailField()
    phone = models.CharField(max_length=20)

    # Shipping Address
    street_address = models.TextField()
    apartment = models.CharField(max_length=100, blank=True, null=True)
    city = models.CharField(max_length=100)
    state = models.CharField(max_length=100)
    postcode = models.CharField(max_length=20)
    country = models.CharField(max_length=100)

    # Order Details
    subtotal = models.DecimalField(max_digits=10, decimal_places=2)
    shipping_cost = models.DecimalField(max_digits=10, decimal_places=2, default=2.00)
    discount = models.DecimalField(max_digits=10, decimal_places=2, default=0.00)
    total = models.DecimalField(max_digits=10, decimal_places=2)

    # Payment and Status
    payment_method = models.CharField(max_length=50)
    order_status = models.CharField(max_length=20, default='pending')

    # Additional Info
    order_notes = models.TextField(blank=True, null=True)
    create_account = models.BooleanField(default=False)
    different_shipping = models.BooleanField(default=False)

    # Timestamps
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f'Order {self.order_number} - {self.first_name} {self.last_name}'

    def get_full_name(self):
        return f'{self.first_name} {self.last_name}'

    def get_shipping_address(self):
        address_parts = [
            self.street_address,
            self.apartment,
            f'{self.city}, {self.state} {self.postcode}',
            self.country
        ]
        return ', '.join(filter(None, address_parts))


class OrderItem(models.Model):
    """Individual items in an order"""
    order = models.ForeignKey(Order, on_delete=models.CASCADE)
    product = models.ForeignKey(Products, on_delete=models.CASCADE)
    quantity = models.PositiveIntegerField()
    price = models.DecimalField(max_digits=10, decimal_places=2)  # Price at time of order

    def __str__(self):
        return f'{self.quantity}x {self.product.productName}'

    def get_subtotal(self):
        return self.price * self.quantity


class Blog(models.Model):
    CATEGORY_CHOICES = [
        ('fan_favorites', 'Fan Favorites'),
        ('drinks', 'Drinks'),
        ('food', 'Food'),
        ('at_home_coffee', 'At Home Coffee'),
    ]

    title = models.CharField(max_length=200)
    content = models.TextField()
    author = models.CharField(max_length=100)
    category = models.CharField(max_length=50, choices=CATEGORY_CHOICES, default='all')
    tags = models.CharField(max_length=500, blank=True, help_text="Comma-separated tags")
    image = models.ImageField(upload_to='images/blog/', null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return self.title

    def get_tags_list(self):
        """Return tags as a list"""
        return [tag.strip() for tag in self.tags.split(',') if tag.strip()]


class SaleOff(models.Model):
    """Model for managing sale/discount products displayed on shop page"""
    product = models.ForeignKey(Products, on_delete=models.CASCADE)
    discount_percentage = models.PositiveIntegerField(help_text="Discount percentage (e.g., 20 for 20%)")
    original_price = models.DecimalField(max_digits=10, decimal_places=2, help_text="Original price before discount")
    sale_price = models.DecimalField(max_digits=10, decimal_places=2, help_text="Discounted price")
    is_active = models.BooleanField(default=True, help_text="Whether this sale item is currently active")
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        verbose_name = "Sale Off Item"
        verbose_name_plural = "Sale Off Items"
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.product.productName} - {self.discount_percentage}% off"

    def save(self, *args, **kwargs):
        """Auto-calculate sale price if not provided"""
        if not self.sale_price and self.original_price and self.discount_percentage:
            discount_amount = (self.original_price * self.discount_percentage) / 100
            self.sale_price = self.original_price - discount_amount
        super().save(*args, **kwargs)
