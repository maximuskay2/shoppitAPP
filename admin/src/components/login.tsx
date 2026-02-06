import { useState } from "react";
import { useNavigate } from "react-router-dom";
// 1. Import Eye Icons
import { BiShow, BiHide } from "react-icons/bi";
import logo from "../assets/Shopittplus-logo.png";
import { apiUrl } from "../lib/api";

const Login = () => {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const navigate = useNavigate();
  const [isLoading, setIsLoading] = useState(false);
  // 2. State for password visibility
  const [showPassword, setShowPassword] = useState(false);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      const res = await fetch(
        apiUrl("/api/v1/admin/auth/login"),
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ email, password }),
        }
      );

      const result = await res.json();

      if (!res.ok) {
        alert(result.message || "An error occurred");
        throw new Error(result.message || "Login failed");
      }

      console.log("Login Success:", result);
      if (result.data && result.data.token) {
        localStorage.setItem("token", result.data.token);
        navigate("/dashboard");
      } else {
        alert("Token not found in response");
      }
    } catch (err) {
      console.error("Error occurred while logging in", err);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="flex items-center justify-center min-h-screen bg-gray-100 font-sans">
      <div className="flex w-full max-w-4xl shadow-2xl rounded-2xl overflow-hidden bg-white min-h-[500px]">
        {/* Left Side - Branding */}
        <div className="hidden md:flex flex-col justify-center items-center w-1/2 bg-[#1F6728] p-10 text-white relative">
          <div className="z-10 text-center">
            <div className="flex justify-center items-center">
              <img src={logo} alt="Shopitplus Logo" />
            </div>
            <h2 className="text-3xl font-semibold mb-2">Welcome Back!</h2>
            <p className="text-green-100 text-lg">
              Sign in to manage your dashboard, orders, and users.
            </p>
          </div>
          <div className="absolute -bottom-20 -left-20 w-60 h-60 bg-green-600 rounded-full opacity-50 blur-xl"></div>
        </div>

        {/* Right Side - Login Form */}
        <div className="w-full md:w-1/2 p-10 flex flex-col justify-center relative">
          <h2 className="text-3xl font-bold text-gray-800 text-center mb-8">
            Admin Login
          </h2>

          <form onSubmit={handleLogin} className="flex flex-col gap-5">
            <div>
              <label className="block text-gray-600 font-semibold mb-2 text-sm">
                Email Address
              </label>
              <input
                type="email"
                className="w-full border border-gray-300 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1F6728] focus:border-transparent transition-all"
                placeholder="admin@example.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>

            {/* 3. Password field with Eye Toggle */}
            <div>
              <label className="block text-gray-600 font-semibold mb-2 text-sm">
                Password
              </label>
              <div className="relative">
                <input
                  type={showPassword ? "text" : "password"}
                  className="w-full border border-gray-300 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1F6728] focus:border-transparent transition-all pr-12"
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#1F6728] transition-colors"
                >
                  {showPassword ? <BiHide size={24} /> : <BiShow size={24} />}
                </button>
              </div>
            </div>

            <div className="flex items-center justify-between text-sm">
              <label className="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" className="accent-[#1F6728]" />
                <span className="text-gray-600">Remember me</span>
              </label>
              <span
                onClick={() => navigate("/forgot-password")}
                className="text-[#1F6728] font-semibold cursor-pointer hover:underline"
              >
                Forgot Password?
              </span>
            </div>

            <button
              type="submit"
              disabled={isLoading}
              className={`w-full text-white font-bold py-3 rounded-lg transition-all shadow-md hover:shadow-lg mt-2 ${
                isLoading
                  ? "bg-green-800 cursor-not-allowed opacity-70"
                  : "bg-[#1F6728] hover:bg-green-800"
              }`}
            >
              {isLoading ? "Signing In..." : "Sign In"}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
};

export default Login;
