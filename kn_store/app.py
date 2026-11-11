from flask import Flask, render_template, request, redirect, url_for, flash, jsonify, send_file, session
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager, UserMixin, login_user, logout_user, login_required, current_user
from werkzeug.security import generate_password_hash, check_password_hash
from werkzeug.utils import secure_filename
from datetime import datetime, timedelta
from functools import wraps
import os, json, io, base64
from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import SimpleDocTemplate, Table, TableStyle, Paragraph, Spacer
from reportlab.lib.units import inch
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import matplotlib.dates as mdates
from sqlalchemy import func
import random

app = Flask(__name__)
app.config['SECRET_KEY'] = 'kn-store-secret-2024'
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///kn_store.db'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.config['UPLOAD_FOLDER'] = 'static/uploads'
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif', 'webp'}

db = SQLAlchemy(app)
login_manager = LoginManager(app)
login_manager.login_view = 'login'

# ─── MODELS ────────────────────────────────────────────────────────────────────

class Admin(UserMixin, db.Model):
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password_hash = db.Column(db.String(256), nullable=False)
    profile_image = db.Column(db.String(256), default='default.png')
    full_name = db.Column(db.String(120), default='')
    phone = db.Column(db.String(20), default='')
    role = db.Column(db.String(20), default='admin')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    theme = db.Column(db.String(10), default='light')

class Category(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    description = db.Column(db.Text, default='')
    color = db.Column(db.String(20), default='#4f46e5')
    icon = db.Column(db.String(50), default='📦')
    products = db.relationship('Product', backref='category', lazy=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

class Product(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200), nullable=False)
    sku = db.Column(db.String(100), unique=True, nullable=False)
    description = db.Column(db.Text, default='')
    price = db.Column(db.Float, nullable=False)
    cost_price = db.Column(db.Float, default=0)
    quantity = db.Column(db.Integer, default=0)
    min_stock = db.Column(db.Integer, default=5)
    category_id = db.Column(db.Integer, db.ForeignKey('category.id'))
    image = db.Column(db.String(256), default='')
    brand = db.Column(db.String(100), default='')
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    sales = db.relationship('Sale', backref='product', lazy=True)

class Sale(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    product_id = db.Column(db.Integer, db.ForeignKey('product.id'), nullable=False)
    quantity = db.Column(db.Integer, nullable=False)
    unit_price = db.Column(db.Float, nullable=False)
    total_price = db.Column(db.Float, nullable=False)
    customer_name = db.Column(db.String(200), default='Walk-in')
    payment_method = db.Column(db.String(50), default='cash')
    notes = db.Column(db.Text, default='')
    sale_date = db.Column(db.DateTime, default=datetime.utcnow)

class StockMovement(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    product_id = db.Column(db.Integer, db.ForeignKey('product.id'), nullable=False)
    movement_type = db.Column(db.String(20))  # in / out / adjustment
    quantity = db.Column(db.Integer)
    reason = db.Column(db.String(200), default='')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    product = db.relationship('Product', backref='movements')

@login_manager.user_loader
def load_user(user_id):
    return Admin.query.get(int(user_id))

# ─── HELPERS ───────────────────────────────────────────────────────────────────

def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def get_theme():
    if current_user.is_authenticated:
        return current_user.theme
    return session.get('theme', 'light')

def seed_data():
    if Admin.query.count() == 0:
        admin = Admin(username='admin', email='admin@knstore.com',
                      password_hash=generate_password_hash('admin123'),
                      full_name='K&N Administrator', role='superadmin')
        db.session.add(admin)

        cats = [
            Category(name='Auto Parts', description='Engine & drivetrain parts', color='#ef4444', icon='⚙️'),
            Category(name='Filters', description='Air, oil & fuel filters', color='#f97316', icon='🔧'),
            Category(name='Accessories', description='Interior & exterior accessories', color='#8b5cf6', icon='✨'),
            Category(name='Lighting', description='Headlights, LEDs & bulbs', color='#eab308', icon='💡'),
            Category(name='Performance', description='Turbo & performance upgrades', color='#10b981', icon='🚀'),
        ]
        db.session.add_all(cats)
        db.session.flush()

        products_data = [
            ('K&N Air Filter HP-1008', 'KN-HP1008', cats[1].id, 45.99, 22.0, 120, 'K&N'),
            ('K&N Oil Filter HP-2009', 'KN-HP2009', cats[1].id, 12.99, 6.0, 250, 'K&N'),
            ('Cold Air Intake System', 'KN-CAI001', cats[4].id, 289.99, 140.0, 35, 'K&N'),
            ('Performance Exhaust Filter', 'KN-PEF02', cats[4].id, 189.99, 90.0, 28, 'K&N'),
            ('Cabin Air Filter', 'KN-CAB005', cats[1].id, 24.99, 12.0, 180, 'K&N'),
            ('LED Headlight Kit H11', 'KN-LED011', cats[3].id, 79.99, 38.0, 65, 'K&N'),
            ('Sport Steering Wheel Cover', 'KN-SWC01', cats[2].id, 34.99, 15.0, 90, 'K&N'),
            ('Throttle Body Spacer', 'KN-TBS003', cats[0].id, 54.99, 26.0, 42, 'K&N'),
            ('High Flow Fuel Filter', 'KN-FF001', cats[1].id, 18.99, 9.0, 200, 'K&N'),
            ('Carbon Fiber Hood Vent', 'KN-HV001', cats[2].id, 129.99, 62.0, 15, 'K&N'),
            ('Turbo Inlet Pipe', 'KN-TIP002', cats[4].id, 149.99, 72.0, 22, 'K&N'),
            ('Air Intake Manifold Seal', 'KN-IMS01', cats[0].id, 9.99, 4.5, 350, 'K&N'),
        ]

        prods = []
        for name, sku, cat_id, price, cost, qty, brand in products_data:
            p = Product(name=name, sku=sku, category_id=cat_id, price=price,
                        cost_price=cost, quantity=qty, brand=brand, min_stock=10)
            db.session.add(p)
            prods.append(p)
        db.session.flush()

        # seed sales last 60 days
        for i in range(180):
            p = random.choice(prods)
            qty = random.randint(1, 5)
            date = datetime.utcnow() - timedelta(days=random.randint(0, 59), hours=random.randint(0,23))
            sale = Sale(product_id=p.id, quantity=qty, unit_price=p.price,
                        total_price=round(p.price * qty, 2),
                        customer_name=random.choice(['John Smith', 'Maria Garcia', 'Walk-in', 'David Lee', 'Sarah Kim']),
                        payment_method=random.choice(['cash', 'card', 'transfer']),
                        sale_date=date)
            db.session.add(sale)
        db.session.commit()

# ─── AUTH ──────────────────────────────────────────────────────────────────────

@app.route('/login', methods=['GET', 'POST'])
def login():
    if current_user.is_authenticated:
        return redirect(url_for('dashboard'))
    if request.method == 'POST':
        user = Admin.query.filter_by(username=request.form['username']).first()
        if user and check_password_hash(user.password_hash, request.form['password']):
            login_user(user)
            return redirect(url_for('dashboard'))
        flash('Invalid credentials', 'error')
    return render_template('login.html', theme=get_theme())

@app.route('/logout')
@login_required
def logout():
    logout_user()
    return redirect(url_for('login'))

# ─── DASHBOARD ─────────────────────────────────────────────────────────────────

@app.route('/')
@app.route('/dashboard')
@login_required
def dashboard():
    today = datetime.utcnow().date()
    today_sales = Sale.query.filter(func.date(Sale.sale_date) == today).all()
    today_revenue = sum(s.total_price for s in today_sales)

    week_start = datetime.utcnow() - timedelta(days=7)
    week_sales = Sale.query.filter(Sale.sale_date >= week_start).all()
    week_revenue = sum(s.total_price for s in week_sales)

    total_products = Product.query.filter_by(is_active=True).count()
    low_stock = Product.query.filter(Product.quantity <= Product.min_stock, Product.is_active==True).count()
    total_categories = Category.query.count()
    total_revenue = db.session.query(func.sum(Sale.total_price)).scalar() or 0

    recent_sales = Sale.query.order_by(Sale.sale_date.desc()).limit(8).all()
    low_stock_items = Product.query.filter(Product.quantity <= Product.min_stock, Product.is_active==True).limit(5).all()

    return render_template('dashboard.html', theme=get_theme(),
        today_revenue=today_revenue, week_revenue=week_revenue,
        total_products=total_products, low_stock=low_stock,
        total_categories=total_categories, total_revenue=total_revenue,
        recent_sales=recent_sales, low_stock_items=low_stock_items)

# ─── PRODUCTS (CRUD) ───────────────────────────────────────────────────────────

@app.route('/products')
@login_required
def products():
    q = request.args.get('q', '')
    cat_id = request.args.get('category', '')
    page = request.args.get('page', 1, type=int)
    query = Product.query.filter_by(is_active=True)
    if q:
        query = query.filter(Product.name.ilike(f'%{q}%') | Product.sku.ilike(f'%{q}%'))
    if cat_id:
        query = query.filter_by(category_id=int(cat_id))
    products = query.order_by(Product.created_at.desc()).paginate(page=page, per_page=12)
    categories = Category.query.all()
    return render_template('products.html', products=products, categories=categories,
                           q=q, cat_id=cat_id, theme=get_theme())

@app.route('/products/add', methods=['GET', 'POST'])
@login_required
def add_product():
    categories = Category.query.all()
    if request.method == 'POST':
        img_filename = ''
        if 'image' in request.files and request.files['image'].filename:
            f = request.files['image']
            if allowed_file(f.filename):
                img_filename = secure_filename(f.filename)
                f.save(os.path.join(app.config['UPLOAD_FOLDER'], img_filename))
        p = Product(
            name=request.form['name'], sku=request.form['sku'],
            description=request.form.get('description', ''),
            price=float(request.form['price']),
            cost_price=float(request.form.get('cost_price', 0)),
            quantity=int(request.form.get('quantity', 0)),
            min_stock=int(request.form.get('min_stock', 5)),
            category_id=int(request.form['category_id']) if request.form.get('category_id') else None,
            brand=request.form.get('brand', ''), image=img_filename
        )
        db.session.add(p)
        db.session.commit()
        db.session.add(StockMovement(product_id=p.id, movement_type='in', quantity=p.quantity, reason='Initial stock'))
        db.session.commit()
        flash('Product added successfully!', 'success')
        return redirect(url_for('products'))
    return render_template('product_form.html', product=None, categories=categories, theme=get_theme())

@app.route('/products/edit/<int:id>', methods=['GET', 'POST'])
@login_required
def edit_product(id):
    p = Product.query.get_or_404(id)
    categories = Category.query.all()
    if request.method == 'POST':
        old_qty = p.quantity
        if 'image' in request.files and request.files['image'].filename:
            f = request.files['image']
            if allowed_file(f.filename):
                p.image = secure_filename(f.filename)
                f.save(os.path.join(app.config['UPLOAD_FOLDER'], p.image))
        p.name = request.form['name']
        p.sku = request.form['sku']
        p.description = request.form.get('description', '')
        p.price = float(request.form['price'])
        p.cost_price = float(request.form.get('cost_price', 0))
        new_qty = int(request.form.get('quantity', 0))
        p.min_stock = int(request.form.get('min_stock', 5))
        p.category_id = int(request.form['category_id']) if request.form.get('category_id') else None
        p.brand = request.form.get('brand', '')
        if new_qty != old_qty:
            diff = new_qty - old_qty
            db.session.add(StockMovement(product_id=p.id,
                movement_type='in' if diff > 0 else 'out',
                quantity=abs(diff), reason='Manual adjustment'))
        p.quantity = new_qty
        db.session.commit()
        flash('Product updated!', 'success')
        return redirect(url_for('products'))
    return render_template('product_form.html', product=p, categories=categories, theme=get_theme())

@app.route('/products/delete/<int:id>', methods=['POST'])
@login_required
def delete_product(id):
    p = Product.query.get_or_404(id)
    p.is_active = False
    db.session.commit()
    flash('Product removed!', 'success')
    return redirect(url_for('products'))

@app.route('/products/<int:id>/json')
@login_required
def product_json(id):
    p = Product.query.get_or_404(id)
    return jsonify({'id': p.id, 'name': p.name, 'price': p.price, 'quantity': p.quantity,
                    'sku': p.sku, 'brand': p.brand, 'description': p.description})

# ─── CATEGORIES ────────────────────────────────────────────────────────────────

@app.route('/categories')
@login_required
def categories():
    cats = Category.query.all()
    return render_template('categories.html', categories=cats, theme=get_theme())

@app.route('/categories/add', methods=['POST'])
@login_required
def add_category():
    c = Category(name=request.form['name'], description=request.form.get('description',''),
                 color=request.form.get('color','#4f46e5'), icon=request.form.get('icon','📦'))
    db.session.add(c)
    db.session.commit()
    flash('Category added!', 'success')
    return redirect(url_for('categories'))

@app.route('/categories/edit/<int:id>', methods=['POST'])
@login_required
def edit_category(id):
    c = Category.query.get_or_404(id)
    c.name = request.form['name']
    c.description = request.form.get('description', '')
    c.color = request.form.get('color', '#4f46e5')
    c.icon = request.form.get('icon', '📦')
    db.session.commit()
    flash('Category updated!', 'success')
    return redirect(url_for('categories'))

@app.route('/categories/delete/<int:id>', methods=['POST'])
@login_required
def delete_category(id):
    c = Category.query.get_or_404(id)
    db.session.delete(c)
    db.session.commit()
    flash('Category deleted!', 'success')
    return redirect(url_for('categories'))

# ─── SALES ─────────────────────────────────────────────────────────────────────

@app.route('/sales')
@login_required
def sales():
    page = request.args.get('page', 1, type=int)
    period = request.args.get('period', 'all')
    now = datetime.utcnow()
    query = Sale.query
    if period == 'today':
        query = query.filter(func.date(Sale.sale_date) == now.date())
    elif period == 'week':
        query = query.filter(Sale.sale_date >= now - timedelta(days=7))
    elif period == 'month':
        query = query.filter(Sale.sale_date >= now - timedelta(days=30))
    sales = query.order_by(Sale.sale_date.desc()).paginate(page=page, per_page=15)
    products = Product.query.filter_by(is_active=True).all()
    return render_template('sales.html', sales=sales, products=products, period=period, theme=get_theme())

@app.route('/sales/add', methods=['POST'])
@login_required
def add_sale():
    p = Product.query.get(int(request.form['product_id']))
    if not p:
        flash('Product not found', 'error')
        return redirect(url_for('sales'))
    qty = int(request.form['quantity'])
    if p.quantity < qty:
        flash(f'Insufficient stock! Available: {p.quantity}', 'error')
        return redirect(url_for('sales'))
    p.quantity -= qty
    sale = Sale(product_id=p.id, quantity=qty, unit_price=p.price,
                total_price=round(p.price * qty, 2),
                customer_name=request.form.get('customer_name', 'Walk-in'),
                payment_method=request.form.get('payment_method', 'cash'),
                notes=request.form.get('notes', ''))
    db.session.add(sale)
    db.session.add(StockMovement(product_id=p.id, movement_type='out', quantity=qty, reason='Sale'))
    db.session.commit()
    flash(f'Sale recorded! Total: ${sale.total_price:.2f}', 'success')
    return redirect(url_for('sales'))

# ─── STOCK ─────────────────────────────────────────────────────────────────────

@app.route('/stock')
@login_required
def stock():
    view = request.args.get('view', 'row')
    cat_id = request.args.get('category', '')
    q = request.args.get('q', '')
    query = Product.query.filter_by(is_active=True)
    if q:
        query = query.filter(Product.name.ilike(f'%{q}%'))
    if cat_id:
        query = query.filter_by(category_id=int(cat_id))
    prods = query.order_by(Product.name).all()
    categories = Category.query.all()
    movements = StockMovement.query.order_by(StockMovement.created_at.desc()).limit(20).all()
    return render_template('stock.html', products=prods, categories=categories,
                           movements=movements, view=view, q=q, cat_id=cat_id, theme=get_theme())

@app.route('/stock/adjust/<int:id>', methods=['POST'])
@login_required
def adjust_stock(id):
    p = Product.query.get_or_404(id)
    qty = int(request.form['quantity'])
    mtype = request.form['type']
    reason = request.form.get('reason', 'Manual adjustment')
    if mtype == 'in':
        p.quantity += qty
    elif mtype == 'out':
        p.quantity = max(0, p.quantity - qty)
    else:
        p.quantity = qty
    db.session.add(StockMovement(product_id=id, movement_type=mtype, quantity=qty, reason=reason))
    db.session.commit()
    flash(f'Stock updated for {p.name}', 'success')
    return redirect(url_for('stock'))

# ─── ANALYTICS ─────────────────────────────────────────────────────────────────

@app.route('/analytics')
@login_required
def analytics():
    return render_template('analytics.html', theme=get_theme())

@app.route('/api/chart/sales-trend')
@login_required
def chart_sales_trend():
    days = int(request.args.get('days', 30))
    start = datetime.utcnow() - timedelta(days=days)
    sales = db.session.query(func.date(Sale.sale_date), func.sum(Sale.total_price))\
        .filter(Sale.sale_date >= start)\
        .group_by(func.date(Sale.sale_date))\
        .order_by(func.date(Sale.sale_date)).all()

    dark = get_theme() == 'dark'
    bg = '#1e1b2e' if dark else '#ffffff'
    tc = '#e2e8f0' if dark else '#1e293b'
    gc = '#7c3aed' if dark else '#4f46e5'

    fig, ax = plt.subplots(figsize=(10, 4))
    fig.patch.set_facecolor(bg)
    ax.set_facecolor(bg)
    if sales:
        dates = [s[0] for s in sales]
        values = [float(s[1]) for s in sales]
        ax.fill_between(range(len(dates)), values, alpha=0.15, color=gc)
        ax.plot(range(len(dates)), values, color=gc, linewidth=2.5, marker='o', markersize=4)
        ax.set_xticks(range(0, len(dates), max(1, len(dates)//6)))
        ax.set_xticklabels([str(dates[i])[-5:] for i in range(0, len(dates), max(1, len(dates)//6))], color=tc, fontsize=9)
    ax.tick_params(colors=tc)
    ax.spines['bottom'].set_color(tc)
    ax.spines['left'].set_color(tc)
    ax.spines['top'].set_visible(False)
    ax.spines['right'].set_visible(False)
    ax.yaxis.tick_left()
    for label in ax.get_yticklabels():
        label.set_color(tc)
    ax.set_title('Sales Revenue Trend', color=tc, fontsize=13, fontweight='bold', pad=12)
    plt.tight_layout()
    buf = io.BytesIO()
    plt.savefig(buf, format='png', dpi=120, bbox_inches='tight', facecolor=bg)
    plt.close()
    buf.seek(0)
    return 'data:image/png;base64,' + base64.b64encode(buf.read()).decode(), 200, {'Content-Type': 'text/plain'}

@app.route('/api/chart/category-sales')
@login_required
def chart_category_sales():
    data = db.session.query(Category.name, func.sum(Sale.total_price))\
        .join(Product, Category.id == Product.category_id)\
        .join(Sale, Product.id == Sale.product_id)\
        .group_by(Category.name).all()

    dark = get_theme() == 'dark'
    bg = '#1e1b2e' if dark else '#ffffff'
    tc = '#e2e8f0' if dark else '#1e293b'
    clrs = ['#7c3aed','#ef4444','#f97316','#10b981','#eab308','#06b6d4','#ec4899']

    fig, ax = plt.subplots(figsize=(8, 4))
    fig.patch.set_facecolor(bg)
    ax.set_facecolor(bg)
    if data:
        names = [d[0] for d in data]
        vals = [float(d[1]) for d in data]
        wedges, texts, autotexts = ax.pie(vals, labels=names, autopct='%1.1f%%',
            colors=clrs[:len(vals)], startangle=90,
            wedgeprops=dict(edgecolor=bg, linewidth=2))
        for t in texts: t.set_color(tc)
        for t in autotexts: t.set_color('white'); t.set_fontsize(9)
    ax.set_title('Sales by Category', color=tc, fontsize=13, fontweight='bold')
    plt.tight_layout()
    buf = io.BytesIO()
    plt.savefig(buf, format='png', dpi=120, bbox_inches='tight', facecolor=bg)
    plt.close()
    buf.seek(0)
    return 'data:image/png;base64,' + base64.b64encode(buf.read()).decode(), 200, {'Content-Type': 'text/plain'}

@app.route('/api/chart/top-products')
@login_required
def chart_top_products():
    data = db.session.query(Product.name, func.sum(Sale.quantity))\
        .join(Sale, Product.id == Sale.product_id)\
        .group_by(Product.id)\
        .order_by(func.sum(Sale.quantity).desc()).limit(8).all()

    dark = get_theme() == 'dark'
    bg = '#1e1b2e' if dark else '#ffffff'
    tc = '#e2e8f0' if dark else '#1e293b'
    gc = '#7c3aed'

    fig, ax = plt.subplots(figsize=(9, 4))
    fig.patch.set_facecolor(bg)
    ax.set_facecolor(bg)
    if data:
        names = [d[0][:20] for d in data]
        vals = [int(d[1]) for d in data]
        bars = ax.barh(names[::-1], vals[::-1], color=gc, alpha=0.85, height=0.6)
        ax.tick_params(colors=tc)
        for spine in ax.spines.values(): spine.set_visible(False)
        for label in ax.get_yticklabels(): label.set_color(tc); label.set_fontsize(9)
        for label in ax.get_xticklabels(): label.set_color(tc)
    ax.set_title('Top Products by Units Sold', color=tc, fontsize=13, fontweight='bold')
    plt.tight_layout()
    buf = io.BytesIO()
    plt.savefig(buf, format='png', dpi=120, bbox_inches='tight', facecolor=bg)
    plt.close()
    buf.seek(0)
    return 'data:image/png;base64,' + base64.b64encode(buf.read()).decode(), 200, {'Content-Type': 'text/plain'}

@app.route('/api/chart/monthly-revenue')
@login_required
def chart_monthly_revenue():
    data = db.session.query(
        func.strftime('%Y-%m', Sale.sale_date),
        func.sum(Sale.total_price),
        func.sum(Sale.quantity)
    ).group_by(func.strftime('%Y-%m', Sale.sale_date))\
     .order_by(func.strftime('%Y-%m', Sale.sale_date)).limit(12).all()

    dark = get_theme() == 'dark'
    bg = '#1e1b2e' if dark else '#ffffff'
    tc = '#e2e8f0' if dark else '#1e293b'

    fig, ax1 = plt.subplots(figsize=(10, 4))
    fig.patch.set_facecolor(bg)
    ax1.set_facecolor(bg)
    if data:
        months = [d[0] for d in data]
        rev = [float(d[1]) for d in data]
        units = [int(d[2]) for d in data]
        x = range(len(months))
        ax1.bar(x, rev, color='#7c3aed', alpha=0.7, label='Revenue $')
        ax2 = ax1.twinx()
        ax2.plot(x, units, color='#f97316', linewidth=2, marker='o', markersize=5, label='Units')
        ax2.tick_params(colors=tc)
        for label in ax2.get_yticklabels(): label.set_color(tc)
        ax2.spines['right'].set_color(tc)
        ax2.spines['top'].set_visible(False)
        ax1.set_xticks(list(x))
        ax1.set_xticklabels(months, rotation=30, ha='right', color=tc, fontsize=8)
        for label in ax1.get_yticklabels(): label.set_color(tc)
        for spine in ['left','bottom']: ax1.spines[spine].set_color(tc)
        for spine in ['top','right']: ax1.spines[spine].set_visible(False)
    ax1.set_title('Monthly Revenue & Units', color=tc, fontsize=13, fontweight='bold')
    plt.tight_layout()
    buf = io.BytesIO()
    plt.savefig(buf, format='png', dpi=120, bbox_inches='tight', facecolor=bg)
    plt.close()
    buf.seek(0)
    return 'data:image/png;base64,' + base64.b64encode(buf.read()).decode(), 200, {'Content-Type': 'text/plain'}

# ─── REPORTS ───────────────────────────────────────────────────────────────────

@app.route('/reports')
@login_required
def reports():
    return render_template('reports.html', theme=get_theme())

@app.route('/reports/generate', methods=['POST'])
@login_required
def generate_report():
    period = request.form.get('period', 'day')
    now = datetime.utcnow()
    if period == 'day':
        start = now.replace(hour=0, minute=0, second=0)
        title = f"Daily Report — {now.strftime('%B %d, %Y')}"
    elif period == 'week':
        start = now - timedelta(days=7)
        title = f"Weekly Report — {start.strftime('%b %d')} to {now.strftime('%b %d, %Y')}"
    elif period == 'month':
        start = now - timedelta(days=30)
        title = f"Monthly Report — {start.strftime('%b %d')} to {now.strftime('%b %d, %Y')}"
    else:
        start = now - timedelta(days=365)
        title = f"Annual Report — {start.strftime('%b %d, %Y')} to {now.strftime('%b %d, %Y')}"

    sales = Sale.query.filter(Sale.sale_date >= start).order_by(Sale.sale_date.desc()).all()
    total_revenue = sum(s.total_price for s in sales)
    total_units = sum(s.quantity for s in sales)
    total_orders = len(sales)

    buf = io.BytesIO()
    doc = SimpleDocTemplate(buf, pagesize=A4, leftMargin=0.75*inch, rightMargin=0.75*inch,
                            topMargin=0.75*inch, bottomMargin=0.75*inch)
    styles = getSampleStyleSheet()
    story = []
    title_style = ParagraphStyle('Title', parent=styles['Title'], fontSize=22,
                                  textColor=colors.HexColor('#4f46e5'), spaceAfter=6)
    story.append(Paragraph('K&N Store Management System', title_style))
    story.append(Paragraph(title, styles['Heading2']))
    story.append(Paragraph(f'Generated: {now.strftime("%Y-%m-%d %H:%M")} | Admin: {current_user.full_name or current_user.username}', styles['Normal']))
    story.append(Spacer(1, 0.2*inch))

    summary_data = [['Metric', 'Value'],
                    ['Total Revenue', f'${total_revenue:,.2f}'],
                    ['Total Orders', str(total_orders)],
                    ['Total Units Sold', str(total_units)],
                    ['Avg Order Value', f'${(total_revenue/total_orders if total_orders else 0):,.2f}']]
    t = Table(summary_data, colWidths=[3*inch, 2.5*inch])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#4f46e5')),
        ('TEXTCOLOR', (0,0), (-1,0), colors.white),
        ('FONTNAME', (0,0), (-1,0), 'Helvetica-Bold'),
        ('FONTSIZE', (0,0), (-1,-1), 11),
        ('BACKGROUND', (0,1), (-1,-1), colors.HexColor('#f8fafc')),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, colors.HexColor('#f1f5f9')]),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor('#e2e8f0')),
        ('ALIGN', (1,1), (-1,-1), 'RIGHT'),
        ('LEFTPADDING', (0,0), (-1,-1), 10),
        ('RIGHTPADDING', (0,0), (-1,-1), 10),
        ('TOPPADDING', (0,0), (-1,-1), 8),
        ('BOTTOMPADDING', (0,0), (-1,-1), 8),
    ]))
    story.append(t)
    story.append(Spacer(1, 0.3*inch))
    story.append(Paragraph('Sales Details', styles['Heading3']))

    if sales:
        headers = ['Date', 'Product', 'Customer', 'Qty', 'Unit Price', 'Total', 'Payment']
        rows = [headers]
        for s in sales[:50]:
            rows.append([
                s.sale_date.strftime('%Y-%m-%d'), s.product.name[:28],
                s.customer_name[:18], str(s.quantity),
                f'${s.unit_price:.2f}', f'${s.total_price:.2f}', s.payment_method
            ])
        widths = [0.95*inch, 2.2*inch, 1.3*inch, 0.4*inch, 0.85*inch, 0.85*inch, 0.7*inch]
        t2 = Table(rows, colWidths=widths)
        t2.setStyle(TableStyle([
            ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#4f46e5')),
            ('TEXTCOLOR', (0,0), (-1,0), colors.white),
            ('FONTNAME', (0,0), (-1,0), 'Helvetica-Bold'),
            ('FONTSIZE', (0,0), (-1,-1), 8),
            ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, colors.HexColor('#f8fafc')]),
            ('GRID', (0,0), (-1,-1), 0.3, colors.HexColor('#e2e8f0')),
            ('ALIGN', (3,1), (-1,-1), 'RIGHT'),
            ('LEFTPADDING', (0,0), (-1,-1), 5),
            ('RIGHTPADDING', (0,0), (-1,-1), 5),
            ('TOPPADDING', (0,0), (-1,-1), 5),
            ('BOTTOMPADDING', (0,0), (-1,-1), 5),
        ]))
        story.append(t2)
    else:
        story.append(Paragraph('No sales data for this period.', styles['Normal']))

    doc.build(story)
    buf.seek(0)
    filename = f'KN_Report_{period}_{now.strftime("%Y%m%d")}.pdf'
    return send_file(buf, as_attachment=True, download_name=filename, mimetype='application/pdf')

# ─── PROFILE ───────────────────────────────────────────────────────────────────

@app.route('/profile', methods=['GET', 'POST'])
@login_required
def profile():
    if request.method == 'POST':
        current_user.full_name = request.form.get('full_name', '')
        current_user.phone = request.form.get('phone', '')
        current_user.email = request.form.get('email', current_user.email)
        if 'profile_image' in request.files and request.files['profile_image'].filename:
            f = request.files['profile_image']
            if allowed_file(f.filename):
                filename = f'profile_{current_user.id}_{secure_filename(f.filename)}'
                f.save(os.path.join(app.config['UPLOAD_FOLDER'], filename))
                current_user.profile_image = filename
        if request.form.get('new_password'):
            if check_password_hash(current_user.password_hash, request.form.get('current_password', '')):
                current_user.password_hash = generate_password_hash(request.form['new_password'])
            else:
                flash('Current password is incorrect', 'error')
                return redirect(url_for('profile'))
        db.session.commit()
        flash('Profile updated successfully!', 'success')
        return redirect(url_for('profile'))
    return render_template('profile.html', theme=get_theme())

@app.route('/toggle-theme', methods=['POST'])
@login_required
def toggle_theme():
    current_user.theme = 'dark' if current_user.theme == 'light' else 'light'
    db.session.commit()
    return jsonify({'theme': current_user.theme})

@app.route('/api/stats')
@login_required
def api_stats():
    today = datetime.utcnow().date()
    today_rev = db.session.query(func.sum(Sale.total_price))\
        .filter(func.date(Sale.sale_date) == today).scalar() or 0
    total_rev = db.session.query(func.sum(Sale.total_price)).scalar() or 0
    return jsonify({
        'today_revenue': round(float(today_rev), 2),
        'total_revenue': round(float(total_rev), 2),
        'total_products': Product.query.filter_by(is_active=True).count(),
        'low_stock': Product.query.filter(Product.quantity <= Product.min_stock, Product.is_active==True).count()
    })

if __name__ == '__main__':
    with app.app_context():
        db.create_all()
        seed_data()
    app.run(debug=True, port=5050)
