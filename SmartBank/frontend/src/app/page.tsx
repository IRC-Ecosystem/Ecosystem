"use client";

import LandingNavbar from "@/components/landing/LandingNavbar";
import HeroSection from "@/components/landing/HeroSection";
import ArchitectureDiagram from "@/components/landing/ArchitectureDiagram";
import AboutSection from "@/components/landing/AboutSection";
import ServicesGrid from "@/components/landing/ServicesGrid";
import SecuritySection from "@/components/landing/SecuritySection";
import RolesCards from "@/components/landing/RolesCards";
import FeeTable from "@/components/landing/FeeTable";
import CTAFooter from "@/components/landing/CTAFooter";

export default function LandingPage() {
  return (
    <>
      <LandingNavbar />
      <main className="flex-1 flex flex-col">
        <HeroSection />
        <div id="arsitektur">
          <ArchitectureDiagram />
        </div>
        <div id="tentang">
          <AboutSection />
        </div>
        <div id="layanan">
          <ServicesGrid />
        </div>
        <div id="keamanan">
          <SecuritySection />
        </div>
        <div id="peran">
          <RolesCards />
        </div>
        <div id="biaya">
          <FeeTable />
        </div>
        <CTAFooter />
      </main>
    </>
  );
}
