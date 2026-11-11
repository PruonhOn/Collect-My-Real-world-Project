// src/pages/misc/Landing.jsx
// Owner: Lin Sreydeth - modern landing page with dark/light mode (uses ThemeContext)
import React from 'react';
import { Link } from 'react-router-dom';
import {
  BookOpen, Users, GraduationCap, ArrowRight, CheckCircle,
  Star, TrendingUp, Award, Zap, Shield, Moon, Sun
} from 'lucide-react';
import Button from '@/components/ui/Button';
import Footer from '@/components/layout/Footer';
import { useTheme } from '@/contexts/ThemeContext';

const features = [
  {
    icon: BookOpen,
    title: 'Smart Course Management',
    description: 'AI-powered course scheduling and enrollment with real-time updates and analytics.',
    color: 'from-blue-500 to-cyan-500',
  },
  {
    icon: Users,
    title: 'Student Hub',
    description: 'Seamless access to grades, attendance, resources, and personalized learning paths.',
    color: 'from-purple-500 to-pink-500',
  },
  {
    icon: GraduationCap,
    title: 'Instructor Suite',
    description: 'Advanced tools for grading, analytics, and student engagement tracking.',
    color: 'from-orange-500 to-red-500',
  },
  {
    icon: TrendingUp,
    title: 'Performance Analytics',
    description: 'Deep insights into student progress with predictive analytics and reports.',
    color: 'from-green-500 to-emerald-500',
  },
  {
    icon: Zap,
    title: 'Lightning Fast',
    description: 'Optimized performance with instant load times and smooth interactions.',
    color: 'from-yellow-500 to-orange-500',
  },
  {
    icon: Shield,
    title: 'Secure & Reliable',
    description: 'Enterprise-grade security with 99.9% uptime and data encryption.',
    color: 'from-indigo-500 to-purple-500',
  },
];

const stats = [
  { value: '50K+', label: 'Active Students' },
  { value: '1,000+', label: 'Institutions' },
  { value: '99.9%', label: 'Uptime' },
  { value: '4.9/5', label: 'User Rating' },
];

const Landing = () => {
  const { theme, toggleTheme } = useTheme();
  const darkMode = theme === 'dark';

  return (
    <div className={`min-h-screen flex flex-col transition-colors duration-300 ${darkMode ? 'bg-gray-900' : 'bg-gray-50'}`}>
      {/* Header */}
      <header className={`fixed top-0 left-0 right-0 z-50 backdrop-blur-lg border-b transition-colors duration-300 ${
        darkMode ? 'bg-gray-900/80 border-gray-800' : 'bg-white/80 border-gray-200'
      }`}>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-20">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-gradient-to-br from-primary-600 to-primary-700 rounded-xl flex items-center justify-center shadow-lg shadow-primary-500/30">
                <GraduationCap className="w-7 h-7 text-white" />
              </div>
              <div>
                <span className={`text-xl font-bold block leading-none transition-colors ${darkMode ? 'text-white' : 'text-gray-900'}`}>
                  EduManage
                </span>
                <span className={`text-xs transition-colors ${darkMode ? 'text-gray-400' : 'text-gray-500'}`}>Smart Education Platform</span>
              </div>
            </div>

            <div className="flex items-center gap-3">
              {/* Theme Toggle */}
              <button
                onClick={toggleTheme}
                className={`p-2 rounded-lg transition-colors ${
                  darkMode ? 'bg-gray-800 text-yellow-400 hover:bg-gray-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                }`}
                aria-label="Toggle theme"
              >
                {darkMode ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
              </button>

              <Link to="/auth/login-student">
                <Button variant="ghost" size="sm" className={darkMode ? 'text-gray-300 hover:text-white hover:bg-gray-800' : ''}>
                  Student Login
                </Button>
              </Link>

              <Link to="/auth/login-admin">
                <Button variant="primary" size="sm" className="shadow-lg shadow-primary-500/30">
                  Admin Login
                </Button>
              </Link>
            </div>
          </div>
        </div>
      </header>

      {/* Hero Section */}
      <section className={`relative pt-32 pb-20 px-4 sm:px-6 lg:px-8 overflow-hidden transition-colors duration-300 ${darkMode ? 'bg-gray-900' : ''}`}>
        {/* Animated Background */}
        <div className={`absolute inset-0 transition-opacity duration-300 ${darkMode ? 'bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900' : 'bg-gradient-to-br from-primary-50 via-purple-50 to-pink-50'}`}>
          <div className={`absolute top-0 left-0 w-96 h-96 rounded-full blur-3xl animate-pulse ${darkMode ? 'bg-primary-900/30' : 'bg-primary-300/20'}`}></div>
          <div className={`absolute bottom-0 right-0 w-96 h-96 rounded-full blur-3xl animate-pulse delay-1000 ${darkMode ? 'bg-purple-900/30' : 'bg-purple-300/20'}`}></div>
        </div>

        <div className="relative max-w-7xl mx-auto">
          <div className="text-center">
            <div className={`inline-flex items-center gap-2 px-4 py-2 backdrop-blur rounded-full border shadow-lg mb-8 transition-colors duration-300 ${darkMode ? 'bg-gray-800/80 border-gray-700' : 'bg-white/80 border-gray-200'}`}>
              <Award className="w-4 h-4 text-primary-600" />
              <span className={`text-sm font-medium transition-colors ${darkMode ? 'text-gray-300' : 'text-gray-700'}`}>
                Trusted by 1,000+ Institutions Worldwide
              </span>
            </div>

            <h1 className={`text-6xl md:text-7xl font-black mb-6 leading-tight transition-colors duration-300 ${darkMode ? 'text-white' : 'text-gray-900'}`}>
              Education Made
              <span className="block bg-gradient-to-r from-primary-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                Effortless
              </span>
            </h1>

            <p className={`text-xl md:text-2xl mb-10 max-w-3xl mx-auto leading-relaxed transition-colors duration-300 ${darkMode ? 'text-gray-400' : 'text-gray-600'}`}>
              Transform your institution with our next-generation platform. Streamline operations, engage students, and unlock powerful insights.
            </p>

            <div className="flex flex-col sm:flex-row items-center justify-center gap-4 mb-12">
              <Link to="/auth/register-student">
                <Button variant="primary" size="lg" className="shadow-2xl shadow-primary-500/40 hover:shadow-primary-500/60 transition-shadow group">
                  Start Free Trial
                  <ArrowRight className="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" />
                </Button>
              </Link>

              <Button variant="outline" size="lg" className={`border-2 transition-colors ${darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'}`}>
                Watch Demo
              </Button>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto">
              {stats.map((stat, index) => (
                <div key={index} className={`backdrop-blur rounded-2xl p-6 shadow-lg border transition-colors duration-300 ${darkMode ? 'bg-gray-800/80 border-gray-700' : 'bg-white/80 border-gray-200'}`}>
                  <div className={`text-3xl font-bold mb-1 transition-colors ${darkMode ? 'text-white' : 'text-gray-900'}`}>{stat.value}</div>
                  <div className={`text-sm transition-colors ${darkMode ? 'text-gray-400' : 'text-gray-600'}`}>{stat.label}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Features */}
      <section className={`py-20 px-4 sm:px-6 lg:px-8 transition-colors duration-300 ${darkMode ? 'bg-gray-800' : 'bg-white'}`}>
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <div className={`inline-flex items-center gap-2 px-4 py-2 rounded-full mb-4 transition-colors duration-300 ${darkMode ? 'bg-primary-900/30' : 'bg-primary-50'}`}>
              <Star className="w-4 h-4 text-primary-600" />
              <span className="text-sm font-semibold text-primary-600">FEATURES</span>
            </div>

            <h2 className={`text-4xl md:text-5xl font-bold mb-4 transition-colors duration-300 ${darkMode ? 'text-white' : 'text-gray-900'}`}>
              Everything You Need, All in One Place
            </h2>
            <p className={`text-xl max-w-2xl mx-auto transition-colors duration-300 ${darkMode ? 'text-gray-400' : 'text-gray-600'}`}>
              Powerful tools designed to streamline every aspect of education management
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {features.map((feature, index) => (
              <div key={index} className={`group relative rounded-2xl p-8 border hover:shadow-2xl transition-all duration-300 overflow-hidden ${darkMode ? 'bg-gray-900 border-gray-700 hover:border-gray-600' : 'bg-white border-gray-200 hover:border-transparent'}`}>
                <div className={`absolute inset-0 bg-gradient-to-br ${feature.color} opacity-0 group-hover:opacity-5 transition-opacity`}></div>

                <div className="relative">
                  <div className={`w-14 h-14 bg-gradient-to-br ${feature.color} rounded-xl flex items-center justify-center mb-6 shadow-lg group-hover:scale-110 transition-transform`}>
                    <feature.icon className="w-7 h-7 text-white" />
                  </div>

                  <h3 className={`text-xl font-bold mb-3 group-hover:text-primary-600 transition-colors ${darkMode ? 'text-white' : 'text-gray-900'}`}>
                    {feature.title}
                  </h3>

                  <p className={`leading-relaxed transition-colors ${darkMode ? 'text-gray-400' : 'text-gray-600'}`}>
                    {feature.description}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Benefits */}
      <section className={`py-20 px-4 sm:px-6 lg:px-8 transition-colors duration-300 ${darkMode ? 'bg-gray-900' : 'bg-gradient-to-br from-gray-50 to-gray-100'}`}>
        <div className="max-w-7xl mx-auto">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            <div>
              <div className={`inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6 shadow-sm transition-colors duration-300 ${darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white'}`}>
                <CheckCircle className="w-4 h-4 text-green-600" />
                <span className={`text-sm font-semibold transition-colors ${darkMode ? 'text-gray-300' : 'text-gray-700'}`}>WHY CHOOSE US</span>
              </div>

              <h2 className={`text-4xl md:text-5xl font-bold mb-6 transition-colors duration-300 ${darkMode ? 'text-white' : 'text-gray-900'}`}>
                Built for Modern Education
              </h2>

              <p className={`text-lg mb-8 transition-colors duration-300 ${darkMode ? 'text-gray-400' : 'text-gray-600'}`}>
                We understand the challenges of managing educational institutions. That's why we've created a platform that simplifies complex processes.
              </p>

              <div className="space-y-4">
                {[
                  'Intuitive interface that requires no training',
                  'Real-time collaboration and communication',
                  'Mobile-first design for on-the-go access',
                  'Automated workflows save hours every week',
                  'Comprehensive reporting and analytics',
                ].map((benefit, index) => (
                  <div key={index} className="flex items-start gap-3">
                    <div className="w-6 h-6 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                      <CheckCircle className="w-4 h-4 text-primary-600" />
                    </div>
                    <span className={`transition-colors ${darkMode ? 'text-gray-300' : 'text-gray-700'}`}>{benefit}</span>
                  </div>
                ))}
              </div>
            </div>

            <div className="relative">
              <div className={`rounded-3xl p-8 shadow-2xl transition-colors duration-300 ${darkMode ? 'bg-gradient-to-br from-gray-800 to-gray-900' : 'bg-gradient-to-br from-primary-600 to-purple-600'}`}>
                <div className={`rounded-2xl p-6 mb-4 transition-colors duration-300 ${darkMode ? 'bg-gray-700/50' : 'bg-white'}`}>
                  <div className="flex items-center gap-3 mb-4">
                    <div className={`w-10 h-10 rounded-full transition-colors ${darkMode ? 'bg-gray-600' : 'bg-primary-100'}`}></div>
                    <div className="flex-1">
                      <div className={`h-4 rounded mb-1 transition-colors ${darkMode ? 'bg-gray-600' : 'bg-gray-200'}`}></div>
                      <div className={`h-3 rounded w-2/3 transition-colors ${darkMode ? 'bg-gray-700' : 'bg-gray-100'}`}></div>
                    </div>
                  </div>

                  <div className="space-y-2">
                    <div className={`h-3 rounded transition-colors ${darkMode ? 'bg-gray-600' : 'bg-gray-100'}`}></div>
                    <div className={`h-3 rounded w-3/4 transition-colors ${darkMode ? 'bg-gray-600' : 'bg-gray-100'}`}></div>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-white/20 backdrop-blur rounded-xl p-4">
                    <div className="h-8 bg-white/30 rounded mb-2"></div>
                    <div className="h-3 bg-white/20 rounded"></div>
                  </div>
                  <div className="bg-white/20 backdrop-blur rounded-xl p-4">
                    <div className="h-8 bg-white/30 rounded mb-2"></div>
                    <div className="h-3 bg-white/20 rounded"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="relative py-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-primary-600 via-purple-600 to-pink-600">
          <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-20"></div>
        </div>

        <div className="relative max-w-4xl mx-auto text-center">
          <div className="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur rounded-full mb-6">
            <Zap className="w-4 h-4 text-white" />
            <span className="text-sm font-semibold text-white">GET STARTED TODAY</span>
          </div>

          <h2 className="text-4xl md:text-5xl font-bold text-white mb-6">Ready to Transform Your Institution?</h2>
          
          <p className="text-xl text-white/90 mb-10 max-w-2xl mx-auto">
            Join thousands of institutions already using our platform. 
            Start your free trial today - no credit card required.
          </p>

          <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <Link to="/auth/register-student">
              <Button variant="secondary" size="lg" className="shadow-2xl hover:scale-105 transition-transform">
                Start Free Trial
                <ArrowRight className="w-5 h-5 ml-2" />
              </Button>
            </Link>
            <Button variant="ghost" size="lg" className="text-white border-2 border-white/30 hover:bg-white/10 backdrop-blur">
              Schedule Demo
            </Button>
          </div>

          <p className="text-white/80 text-sm mt-8">✓ Free 30-day trial  ✓ No credit card required  ✓ Cancel anytime</p>
        </div>
      </section>

      <Footer />
    </div>
  );
};

export default Landing;
