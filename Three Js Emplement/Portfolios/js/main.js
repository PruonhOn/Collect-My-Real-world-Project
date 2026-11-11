import * as THREE from 'three';

const isMobile = window.innerWidth < 768;
const particlesCount = isMobile ? 1200 : 4000;

let mouseX = 0;
let mouseY = 0;
let targetMouseX = 0;
let targetMouseY = 0;
let scrollY = 0;

const scene = new THREE.Scene();
scene.background = null;

const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 2000);
camera.position.z = 500;

const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

const canvasContainer = document.getElementById('three-canvas');
canvasContainer.appendChild(renderer.domElement);

const ambientLight = new THREE.AmbientLight(0x222244, 0.5);
scene.add(ambientLight);

const light1 = new THREE.PointLight(0x00d4ff, 2, 800);
light1.position.set(300, 200, 300);
scene.add(light1);

const light2 = new THREE.PointLight(0x7c3aed, 2, 800);
light2.position.set(-300, -200, 300);
scene.add(light2);

const light3 = new THREE.PointLight(0x0aff9d, 1, 600);
light3.position.set(0, 400, -200);
scene.add(light3);

function createParticles() {
    const positions = new Float32Array(particlesCount * 3);
    const colors = new Float32Array(particlesCount * 3);
    const sizes = new Float32Array(particlesCount);

    const colorPalette = [
        new THREE.Color(0x00d4ff),
        new THREE.Color(0x7c3aed),
        new THREE.Color(0x0aff9d),
        new THREE.Color(0xffffff),
    ];

    for (let i = 0; i < particlesCount; i++) {
        const radius = 300 + Math.random() * 700;
        const theta = Math.random() * Math.PI * 2;
        const phi = Math.acos(2 * Math.random() - 1);

        positions[i * 3] = radius * Math.sin(phi) * Math.cos(theta);
        positions[i * 3 + 1] = radius * Math.sin(phi) * Math.sin(theta);
        positions[i * 3 + 2] = radius * Math.cos(phi);

        const color = colorPalette[Math.floor(Math.random() * colorPalette.length)];
        colors[i * 3] = color.r;
        colors[i * 3 + 1] = color.g;
        colors[i * 3 + 2] = color.b;

        sizes[i] = 0.5 + Math.random() * 2;
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
    geometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));

    const material = new THREE.PointsMaterial({
        size: isMobile ? 1.5 : 2.5,
        vertexColors: true,
        transparent: true,
        opacity: 0.9,
        sizeAttenuation: true,
        blending: THREE.AdditiveBlending,
        depthWrite: false,
    });

    const particles = new THREE.Points(geometry, material);
    return particles;
}

const particles = createParticles();
scene.add(particles);

function createCenterShape() {
    const group = new THREE.Group();

    const geometry = new THREE.TorusKnotGeometry(40, 12, 180, 24);
    const material = new THREE.MeshPhysicalMaterial({
        color: 0x00d4ff,
        emissive: 0x00d4ff,
        emissiveIntensity: 0.15,
        metalness: 0.3,
        roughness: 0.2,
        clearcoat: 0.8,
        clearcoatRoughness: 0.3,
        transparent: true,
        opacity: 0.9,
    });
    const mainMesh = new THREE.Mesh(geometry, material);
    group.add(mainMesh);

    const wireMat = new THREE.MeshBasicMaterial({
        color: 0x7c3aed,
        wireframe: true,
        transparent: true,
        opacity: 0.15,
    });
    const wireMesh = new THREE.Mesh(geometry.clone(), wireMat);
    wireMesh.scale.set(1.02, 1.02, 1.02);
    group.add(wireMesh);

    return group;
}

const centerShape = createCenterShape();
scene.add(centerShape);

function createFloatingShapes() {
    const shapes = [];
    const geometries = [
        new THREE.IcosahedronGeometry(8, 0),
        new THREE.OctahedronGeometry(7, 0),
        new THREE.DodecahedronGeometry(9, 0),
        new THREE.TetrahedronGeometry(10, 0),
        new THREE.BoxGeometry(10, 10, 10),
        new THREE.SphereGeometry(7, 12, 12),
        new THREE.TorusGeometry(8, 3, 16, 32),
        new THREE.ConeGeometry(8, 14, 8),
    ];

    const colors = [0x00d4ff, 0x7c3aed, 0x0aff9d, 0xff6b6b, 0xffd93d, 0x6c5ce7];

    geometries.forEach((geo, i) => {
        const color = colors[i % colors.length];
        const mat = new THREE.MeshPhysicalMaterial({
            color: color,
            emissive: color,
            emissiveIntensity: 0.1,
            metalness: 0.4,
            roughness: 0.3,
            transparent: true,
            opacity: 0.6,
            wireframe: Math.random() > 0.5,
        });

        const mesh = new THREE.Mesh(geo, mat);
        const angle = (i / geometries.length) * Math.PI * 2;
        const radius = 150 + Math.random() * 100;
        mesh.position.set(
            Math.cos(angle) * radius,
            Math.sin(angle * 2) * 80,
            (Math.random() - 0.5) * 200
        );
        mesh.rotation.set(Math.random() * Math.PI, Math.random() * Math.PI, 0);
        mesh.userData = {
            speed: 0.005 + Math.random() * 0.02,
            angle: angle,
            radius: radius,
            floatSpeed: 0.5 + Math.random() * 1.5,
            floatAmp: 5 + Math.random() * 10,
            phase: Math.random() * Math.PI * 2,
        };
        scene.add(mesh);
        shapes.push(mesh);
    });

    return shapes;
}

const floatingShapes = createFloatingShapes();

document.addEventListener('mousemove', (e) => {
    targetMouseX = (e.clientX / window.innerWidth) * 2 - 1;
    targetMouseY = -(e.clientY / window.innerHeight) * 2 + 1;
});

document.addEventListener('touchmove', (e) => {
    if (e.touches.length === 1) {
        targetMouseX = (e.touches[0].clientX / window.innerWidth) * 2 - 1;
        targetMouseY = -(e.touches[0].clientY / window.innerHeight) * 2 + 1;
    }
}, { passive: true });

window.addEventListener('scroll', () => {
    scrollY = window.scrollY;
});

window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
});

document.querySelectorAll('.project-card').forEach(card => {
    const preview = card.querySelector('.project-3d-preview');
    if (!preview) return;

    const demoScene = new THREE.Scene();
    const demoCamera = new THREE.PerspectiveCamera(50, preview.clientWidth / preview.clientHeight, 0.1, 100);
    const demoRenderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    demoRenderer.setSize(preview.clientWidth, preview.clientHeight);
    demoRenderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    preview.appendChild(demoRenderer.domElement);

    const demoGeo = new THREE.IcosahedronGeometry(12, 1);
    const demoMat = new THREE.MeshPhysicalMaterial({
        color: new THREE.Color().setHSL(Math.random(), 0.8, 0.5),
        emissive: new THREE.Color().setHSL(Math.random(), 0.8, 0.3),
        emissiveIntensity: 0.3,
        metalness: 0.3,
        roughness: 0.2,
        wireframe: false,
    });
    const demoMesh = new THREE.Mesh(demoGeo, demoMat);
    demoScene.add(demoMesh);

    const wireMat2 = new THREE.MeshBasicMaterial({
        color: 0xffffff,
        wireframe: true,
        transparent: true,
        opacity: 0.15,
    });
    const wireMesh2 = new THREE.Mesh(demoGeo.clone(), wireMat2);
    wireMesh2.scale.set(1.05, 1.05, 1.05);
    demoMesh.add(wireMesh2);

    demoCamera.position.z = 35;

    function animateDemo() {
        requestAnimationFrame(animateDemo);
        demoMesh.rotation.x += 0.008;
        demoMesh.rotation.y += 0.015;
        demoRenderer.render(demoScene, demoCamera);
    }
    animateDemo();

    const resizeObserver = new ResizeObserver(() => {
        const w = preview.clientWidth;
        const h = preview.clientHeight;
        demoCamera.aspect = w / h;
        demoCamera.updateProjectionMatrix();
        demoRenderer.setSize(w, h);
    });
    resizeObserver.observe(preview);
});

let time = 0;

function animate() {
    requestAnimationFrame(animate);
    time += 0.01;

    mouseX += (targetMouseX - mouseX) * 0.05;
    mouseY += (targetMouseY - mouseY) * 0.05;

    const scrollFactor = scrollY / (document.body.scrollHeight - window.innerHeight);

    particles.rotation.y += 0.0003;
    particles.rotation.x += (mouseY * 0.0003 - particles.rotation.x) * 0.02;
    particles.rotation.y += (mouseX * 0.0003 - particles.rotation.y) * 0.02;

    centerShape.rotation.x += 0.005;
    centerShape.rotation.y += 0.01;

    const idleBob = Math.sin(time * 0.5) * 5;
    centerShape.position.y = idleBob;

    const scalePulse = 1 + Math.sin(time * 1.5) * 0.02;
    centerShape.scale.set(scalePulse, scalePulse, scalePulse);

    floatingShapes.forEach((shape, i) => {
        const data = shape.userData;
        shape.rotation.x += data.speed * 0.8;
        shape.rotation.y += data.speed;
        shape.rotation.z += data.speed * 0.5;

        const floatOffset = Math.sin(time * data.floatSpeed + data.phase) * data.floatAmp;
        shape.position.y += (data.radius * 0.1 * Math.sin(time * 0.3 + data.angle) + floatOffset - shape.position.y) * 0.02;

        const originalY = data.radius * 0.1 * Math.sin(time * 0.3 + data.angle) + floatOffset;
        shape.position.y += (originalY - shape.position.y) * 0.02;
    });

    const camX = mouseX * 30 + scrollFactor * 20;
    const camY = mouseY * 20 - scrollFactor * 40;
    camera.position.x += (camX - camera.position.x) * 0.02;
    camera.position.y += (camY - camera.position.y) * 0.02;
    camera.lookAt(0, 0, 0);

    renderer.render(scene, camera);
}

animate();

const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px',
};

const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');

            const barFills = entry.target.querySelectorAll('.bar-fill');
            barFills.forEach(bar => {
                const width = bar.getAttribute('data-width');
                if (width) {
                    setTimeout(() => {
                        bar.style.width = width + '%';
                    }, 200);
                }
            });
        }
    });
}, observerOptions);

document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

const statObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const numbers = entry.target.querySelectorAll('.stat-number');
            numbers.forEach(num => animateCounter(num));
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('.about-stats').forEach(el => statObserver.observe(el));

function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-target'));
    const duration = 2000;
    const start = performance.now();

    function update(currentTime) {
        const elapsed = currentTime - start;
        const progress = Math.min(elapsed / duration, 1);
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(easeOut * target);

        if (target > 0) {
            element.textContent = current + '+';
        } else {
            element.textContent = '0';
        }

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }

    requestAnimationFrame(update);
}

const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 50);
});

const navToggle = document.querySelector('.nav-toggle');
const navLinks = document.querySelector('.nav-links');
const navLinkItems = document.querySelectorAll('.nav-links a');

if (navToggle) {
    navToggle.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        document.body.style.overflow = navLinks.classList.contains('active') ? 'hidden' : '';
    });
}

navLinkItems.forEach(link => {
    link.addEventListener('click', () => {
        navLinks.classList.remove('active');
        document.body.style.overflow = '';
    });
});

const cursor = document.querySelector('.cursor-follower');
if (cursor) {
    document.addEventListener('mousemove', (e) => {
        cursor.style.left = e.clientX + 'px';
        cursor.style.top = e.clientY + 'px';
    });

    document.querySelectorAll('a, button, .project-card').forEach(el => {
        el.addEventListener('mouseenter', () => cursor.style.transform = 'translate(-50%, -50%) scale(1.5)');
        el.addEventListener('mouseleave', () => cursor.style.transform = 'translate(-50%, -50%) scale(1)');
    });
}

const projectCards = document.querySelectorAll('.project-card');
projectCards.forEach(card => {
    card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        const rotateX = (y - centerY) / centerY * -8;
        const rotateY = (x - centerX) / centerX * 8;
        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px)`;
    });

    card.addEventListener('mouseleave', () => {
        card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
    });
});

const contactForm = document.getElementById('contactForm');
if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const btn = contactForm.querySelector('.btn');
        const originalText = btn.textContent;
        btn.textContent = 'Message Sent!';
        btn.style.background = 'linear-gradient(135deg, #0aff9d, #00d4ff)';
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '';
            contactForm.reset();
        }, 3000);
    });
}
