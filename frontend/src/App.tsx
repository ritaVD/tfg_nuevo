import { BrowserRouter, Routes, Route } from 'react-router-dom'
import { AuthProvider } from './context/AuthContext'
import { ToastProvider } from './components/Toast'
import Navbar from './components/Navbar'
import Footer from './components/Footer'
import PrivateRoute from './components/PrivateRoute'
import HomePage from './pages/HomePage'
import LoginPage from './pages/LoginPage'
import RegisterPage from './pages/RegisterPage'
import BooksPage from './pages/BooksPage'
import ClubsPage from './pages/ClubsPage'
import ClubDetailPage from './pages/ClubDetailPage'
import ShelvesPage from './pages/ShelvesPage'
import ProfilePage from './pages/ProfilePage'
import PublicProfilePage from './pages/PublicProfilePage'
import BookDetailPage from './pages/BookDetailPage'
import UsersPage from './pages/UsersPage'
import AdminPage from './pages/AdminPage'

export default function App() {
  return (
    <ToastProvider>
    <AuthProvider>
      <BrowserRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <div style={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }}>
          <Navbar />
          <main style={{ flex: 1 }}>
            <Routes>
              <Route path="/" element={<HomePage />} />
              <Route path="/login" element={<LoginPage />} />
              <Route path="/register" element={<RegisterPage />} />
              <Route path="/books" element={<BooksPage />} />
              <Route path="/books/:externalId" element={<BookDetailPage />} />
              <Route path="/clubs" element={<ClubsPage />} />
              <Route path="/clubs/:id" element={<ClubDetailPage />} />
              <Route path="/users" element={<UsersPage />} />
              <Route path="/users/:id" element={<PublicProfilePage />} />
              <Route path="/admin" element={<AdminPage />} />
              <Route
                path="/shelves"
                element={
                  <PrivateRoute>
                    <ShelvesPage />
                  </PrivateRoute>
                }
              />
              <Route
                path="/profile"
                element={
                  <PrivateRoute>
                    <ProfilePage />
                  </PrivateRoute>
                }
              />
            </Routes>
          </main>
          <Footer />
        </div>
      </BrowserRouter>
    </AuthProvider>
    </ToastProvider>
  )
}
